<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table         = 'messages';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType   = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'sender_id',
        'receiver_id',
        'content',
        'content_original',
        'attachment_type',
        'attachment_url',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'status',
        'is_bot',
    ];

    /**
     * Get messages between current user and another user (for conversation view).
     * Excludes messages the current user has "unsent for me" (hidden).
     */
    public function getConversation(int $userId, int $otherUserId, int $limit = 100): array
    {
        $hiddenIds = $this->getHiddenMessageIdsForUser($userId);
        $builder = $this->withDeleted()->groupStart()
            ->groupStart()
                ->where('sender_id', $userId)->where('receiver_id', $otherUserId)
            ->groupEnd()
            ->orGroupStart()
                ->where('sender_id', $otherUserId)->where('receiver_id', $userId)
            ->groupEnd()
        ->groupEnd();
        if ($hiddenIds !== []) {
            $builder->whereNotIn('id', $hiddenIds);
        }
        return $builder->orderBy('created_at', 'ASC')->findAll($limit);
    }

    /**
     * Message IDs that the user has hidden (unsent for me).
     */
    public function getHiddenMessageIdsForUser(int $userId): array
    {
        $db = $this->db;
        $result = $db->table('message_hides')->select('message_id')->where('user_id', $userId)->get()->getResultArray();
        return array_map('intval', array_column($result, 'message_id'));
    }

    /**
     * Hide message for one user (unsend for me only).
     */
    public function hideForUser(int $messageId, int $userId): bool
    {
        $db = $this->db;
        $exists = $db->table('message_hides')->where('message_id', $messageId)->where('user_id', $userId)->get()->getRowArray();
        if ($exists) {
            return true;
        }
        return $db->table('message_hides')->insert([
            'message_id' => $messageId,
            'user_id'    => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all messages for admin chat logs (includes soft-deleted / unsent for everyone).
     * Ordered newest first.
     */
    public function getAllForAdmin(int $limit = 500): array
    {
        return $this->withDeleted()
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Get list of user IDs that the current user has conversed with (for sidebar).
     */
    public function getConversationPartnerIds(int $userId): array
    {
        $builder = $this->builder();
        $builder->select('sender_id, receiver_id');
        $builder->groupStart();
        $builder->where('sender_id', $userId);
        $builder->orWhere('receiver_id', $userId);
        $builder->groupEnd();
        $rows = $builder->get()->getResultArray();
        $ids = [];
        foreach ($rows as $row) {
            $other = (int) $row['sender_id'] === $userId ? (int) $row['receiver_id'] : (int) $row['sender_id'];
            if ($other !== $userId) {
                $ids[$other] = true;
            }
        }
        return array_keys($ids);
    }
}
