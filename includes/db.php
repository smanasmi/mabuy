<?php
require_once __DIR__ . '/../config.php';

function now_time(): string {
    return date('H:i');
}

// Groups messages into date-divider labels the way WhatsApp/Telegram do:
// "Hari ini" for today, "Kemarin" for yesterday, otherwise "Senin, 29 Jun".
function day_label(int $createdAtMs): string {
    $days = ['Minggu','Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu'];
    $months = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
    $ts = intdiv($createdAtMs, 1000);
    $today = strtotime(date('Y-m-d', time()));
    $that = strtotime(date('Y-m-d', $ts));
    $diffDays = (int)round(($today - $that) / 86400);
    if ($diffDays === 0) return 'Hari ini';
    if ($diffDays === 1) return 'Kemarin';
    return $days[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)];
}

// "Terakhir dilihat" text for offline contacts, based on conversations.updated_at.
function last_seen_label(int $updatedAtMs): string {
    $diffSec = time() - intdiv($updatedAtMs, 1000);
    if ($diffSec < 60) return 'baru saja';
    if ($diffSec < 3600) return floor($diffSec / 60) . ' menit lalu';
    if ($diffSec < 86400) return floor($diffSec / 3600) . ' jam lalu';
    if ($diffSec < 172800) return 'kemarin';
    return floor($diffSec / 86400) . ' hari lalu';
}

function row_to_conversation(array $row, array $messages = [], ?int $joinedAtMs = null): array {
    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'channel' => $row['channel'],
        'handle' => $row['handle'],
        'externalId' => $row['external_id'],
        'tag' => $row['tag'],
        'unread' => (int)$row['unread'],
        'online' => (bool)$row['online'],
        'lastAt' => $row['last_at'],
        'updatedAt' => (int)$row['updated_at'],
        // Human-readable "terakhir dilihat" for offline contacts; not needed while online.
        'lastSeen' => $row['online'] ? null : last_seen_label((int)$row['updated_at']),
        'joined' => $joinedAtMs ? day_label($joinedAtMs) : null,
        'messages' => $messages,
    ];
}

function get_messages_for(array $ids): array {
    if (!$ids) return [];
    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT conversation_id, from_who AS `from`, text, time, status, agent, created_at
         FROM messages WHERE conversation_id IN ($placeholders) ORDER BY id"
    );
    $stmt->execute($ids);
    $byConv = [];
    $joinedByConv = [];
    foreach ($stmt->fetchAll() as $m) {
        $convId = $m['conversation_id'];
        $createdAt = (int)$m['created_at'];
        $byConv[$convId][] = [
            'from' => $m['from'],
            'text' => $m['text'],
            'time' => $m['time'],
            'status' => $m['status'],
            'agent' => $m['agent'],
            // Date-divider label, e.g. "Hari ini" / "Kemarin" / "Senin, 29 Jun".
            'day' => $createdAt ? day_label($createdAt) : null,
        ];
        // Messages are ordered by id, so the first one seen per conversation is the earliest.
        if (!isset($joinedByConv[$convId]) && $createdAt) {
            $joinedByConv[$convId] = $createdAt;
        }
    }
    return [$byConv, $joinedByConv];
}

function get_conversations(): array {
    $pdo = db();
    $rows = $pdo->query('SELECT * FROM conversations ORDER BY updated_at DESC')->fetchAll();
    [$messagesByConv, $joinedByConv] = get_messages_for(array_column($rows, 'id'));
    return array_map(
        fn($r) => row_to_conversation($r, $messagesByConv[$r['id']] ?? [], $joinedByConv[$r['id']] ?? null),
        $rows
    );
}

function get_conversation(string $id): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    [$messagesByConv, $joinedByConv] = get_messages_for([$id]);
    return row_to_conversation($row, $messagesByConv[$id] ?? [], $joinedByConv[$id] ?? null);
}

function upsert_conversation(string $id, string $name, string $channel, ?string $handle, string $externalId): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM conversations WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare(
            'INSERT INTO conversations (id, name, channel, handle, external_id, last_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$id, $name, $channel, $handle, $externalId, now_time(), round(microtime(true) * 1000)]);
    }
    return get_conversation($id);
}

function add_message(string $convId, string $from, string $text, ?string $status = null, ?string $agent = null): array {
    $pdo = db();
    $time = now_time();
    $createdAt = round(microtime(true) * 1000);
    $stmt = $pdo->prepare(
        'INSERT INTO messages (conversation_id, from_who, text, time, status, agent, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$convId, $from, $text, $time, $status, $agent, $createdAt]);

    $upd = $pdo->prepare(
        'UPDATE conversations SET last_at = ?, updated_at = ?, unread = unread + ? WHERE id = ?'
    );
    $upd->execute([$time, $createdAt, $from === 'them' ? 1 : 0, $convId]);

    return ['from' => $from, 'text' => $text, 'time' => $time, 'status' => $status, 'agent' => $agent, 'day' => day_label($createdAt)];
}

function mark_read(string $convId): void {
    $stmt = db()->prepare('UPDATE conversations SET unread = 0 WHERE id = ?');
    $stmt->execute([$convId]);
}

function get_user_by_username(string $username): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_user(string $username, string $passwordHash, ?string $displayName): array {
    $pdo = db();
    $existing = get_user_by_username($username);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, display_name = ? WHERE username = ?');
        $stmt->execute([$passwordHash, $displayName ?: $username, $username]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password_hash, display_name, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $passwordHash, $displayName ?: $username, round(microtime(true) * 1000)]);
    }
    return get_user_by_username($username);
}

function get_all_users(): array {
    return db()->query(
        'SELECT id, username, display_name, created_at FROM users ORDER BY username'
    )->fetchAll();
}

function get_user_by_id(int $id): ?array {
    $stmt = db()->prepare('SELECT id, username, display_name, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// Updates username/display name, and optionally the password (leave $passwordHash null to keep it
// unchanged). Throws PDOException (23000) if the new username collides with a different existing user.
function update_user(int $id, string $username, ?string $displayName, ?string $passwordHash = null): void {
    $pdo = db();
    if ($passwordHash !== null) {
        $stmt = $pdo->prepare('UPDATE users SET username = ?, display_name = ?, password_hash = ? WHERE id = ?');
        $stmt->execute([$username, $displayName ?: $username, $passwordHash, $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET username = ?, display_name = ? WHERE id = ?');
        $stmt->execute([$username, $displayName ?: $username, $id]);
    }
}
