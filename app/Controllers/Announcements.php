<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Announcements extends BaseController
{
    protected AnnouncementModel $announcements;
    protected UserModel $users;

    public function __construct()
    {
        $this->announcements = new AnnouncementModel();
        $this->users        = new UserModel();
        helper(['url', 'form', 'text']);
    }

    public function index(): string
    {
        $role   = strtoupper((string) (session()->get('role') ?? ''));
        $userId = (int) (session()->get('user_id') ?? 0);

        if ($role === 'STUDENT' && $userId > 0) {
            $db = \Config\Database::connect();
            $list = $db->table('announcements a')
                ->select('a.*')
                ->join('notifications n', 'n.reference_id = a.id AND n.reference_table = "announcements" AND n.type = "announcement"', 'inner')
                ->where('n.user_id', $userId)
                ->where('a.deleted_at', null)
                ->groupBy('a.id')
                ->orderBy('a.created_at', 'DESC')
                ->get()
                ->getResultArray();
        } else {
            $list = $this->announcements->orderBy('created_at', 'DESC')->findAll();
        }

        $data = [
            'announcements' => $list,
            'role'          => session()->get('role') ?? 'ADMIN',
            'name'          => session()->get('name') ?? 'User',
        ];
        return view('Announcements/index', $data);
    }

    public function create(): string|RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to create announcements.');
        }
        $role = strtoupper((string) (session()->get('role') ?? ''));
        $teacherSections = [];
        if ($role === 'TEACHER') {
            $teacherId = (int) (session()->get('user_id') ?? 0);
            $db = \Config\Database::connect();
            $rows = $db->table('teacher_sections ts')
                ->select('s.id as section_id, s.name, s.grade_level, s.class_schedule, ts.assignment_role, ts.subject_name')
                ->join('sections s', 's.id = ts.section_id', 'inner')
                ->where('ts.teacher_id', $teacherId)
                ->where('ts.status', 'accepted')
                ->orderBy('s.grade_level', 'ASC')
                ->orderBy('s.name', 'ASC')
                ->get()
                ->getResultArray();

            $grouped = [];
            foreach ($rows as $row) {
                $sid = (int) ($row['section_id'] ?? 0);
                if ($sid < 1) {
                    continue;
                }
                if (! isset($grouped[$sid])) {
                    $grouped[$sid] = [
                        'id'          => $sid,
                        'name'        => (string) ($row['name'] ?? ''),
                        'grade_level' => (string) ($row['grade_level'] ?? ''),
                        'class_schedule' => (string) ($row['class_schedule'] ?? ''),
                        'subjects'    => [],
                    ];
                }
                $assignmentRole = strtoupper((string) ($row['assignment_role'] ?? 'ADVISER'));
                if ($assignmentRole === 'SUBJECT_TEACHER') {
                    $subjectName = trim((string) ($row['subject_name'] ?? ''));
                    if ($subjectName !== '') {
                        $grouped[$sid]['subjects'][] = $subjectName;
                    }
                } else {
                    $rawSchedule = (string) ($grouped[$sid]['class_schedule'] ?? '');
                    if ($rawSchedule !== '') {
                        $decoded = json_decode($rawSchedule, true);
                        if (is_array($decoded) && ! empty($decoded['slots']) && is_array($decoded['slots'])) {
                            foreach ($decoded['slots'] as $slot) {
                                if (empty($slot['adviser_teaches'])) {
                                    continue;
                                }
                                $adviserSubject = trim((string) ($slot['subject'] ?? ''));
                                if ($adviserSubject !== '') {
                                    $grouped[$sid]['subjects'][] = $adviserSubject;
                                }
                            }
                        }
                    }
                }
            }

            foreach ($grouped as $section) {
                $subjects = array_values(array_unique($section['subjects']));
                $label = $section['name'] !== '' ? $section['name'] : ('Section #' . $section['id']);
                if ($subjects !== []) {
                    $label .= ' (' . implode(', ', $subjects) . ')';
                }
                $section['display_label'] = $label;
                $teacherSections[] = $section;
            }
        }

        $data = [
            'role'            => session()->get('role') ?? 'ADMIN',
            'name'            => session()->get('name') ?? 'User',
            'teacherSections' => $teacherSections,
            'audienceOptions' => $this->audienceOptionsForRole($role),
        ];
        return view('Announcements/create', $data);
    }

    public function store(): RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to create announcements.');
        }
        $title   = trim((string) $this->request->getPost('title'));
        $body    = trim((string) $this->request->getPost('body'));
        $userId  = (int) session()->get('user_id');
        $role    = strtoupper((string) (session()->get('role') ?? ''));
        $sectionId = null;
        $audienceType = 'school-wide';

        if ($title === '' || $body === '') {
            return redirect()->back()->withInput()->with('error', 'Title and body are required.');
        }

        if ($role === 'TEACHER') {
            $chosenSectionId = (int) $this->request->getPost('section_id');
            if ($chosenSectionId < 1) {
                return redirect()->back()->withInput()->with('error', 'Please choose a section to announce to.');
            }

            $db = \Config\Database::connect();
            $isAssigned = $db->table('teacher_sections')
                ->where('teacher_id', $userId)
                ->where('section_id', $chosenSectionId)
                ->where('status', 'accepted')
                ->countAllResults() > 0;

            if (! $isAssigned) {
                return redirect()->back()->withInput()->with('error', 'You can only announce to sections assigned to you.');
            }

            $sectionId = $chosenSectionId;
            $audienceType = 'section';
        } elseif (in_array($role, ['PRINCIPAL', 'VICE_PRINCIPAL', 'HEAD_TEACHER', 'ANNOUNCER', 'ADMIN', 'SUPER_ADMIN'], true)) {
            $audienceType = strtolower(trim((string) $this->request->getPost('audience_type')));
            $allowed = ['school-wide', 'teachers-only', 'staff-only', 'students-only'];
            if (! in_array($audienceType, $allowed, true)) {
                return redirect()->back()->withInput()->with('error', 'Please choose a valid audience.');
            }
        }

        $this->announcements->insert([
            'title'         => $title,
            'body'          => $body,
            'created_by'    => $userId,
            'status'        => 'ACTIVE',
            'audience_type' => $audienceType,
            'section_id'    => $sectionId,
        ]);

        $id = $this->announcements->getInsertID();
        $titleShort = character_limiter($title, 60);
        $notifMessage = 'Announce message for you: ' . $titleShort;
        $db = \Config\Database::connect();
        if ($role === 'TEACHER') {
            $userIds = $db->table('users u')
                ->select('u.id')
                ->distinct()
                ->join('teacher_sections ts', 'ts.section_id = u.section_id', 'inner')
                ->where('ts.teacher_id', $userId)
                ->where('ts.status', 'accepted')
                ->where('u.section_id', (int) $sectionId)
                ->where('u.role', 'STUDENT')
                ->where('u.is_active', 1)
                ->get()
                ->getResultArray();
        } else {
            $userIds = $this->resolveRecipientsByAudience($audienceType, $role);
        }

        if ($userIds === []) {
            return redirect()->to(base_url('announcements'))->with('success', 'Announcement published. No student recipients found for your assigned sections.');
        }

        foreach ($userIds as $row) {
            $db->table('notifications')->insert([
                'user_id'          => (int) $row['id'],
                'type'             => 'announcement',
                'reference_table'  => 'announcements',
                'reference_id'     => $id,
                'message'          => $notifMessage,
                'is_read'          => 0,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        if ($role === 'TEACHER') {
            return redirect()->to(base_url('announcements'))->with('success', 'Announcement published. Your students have been notified.');
        }

        return redirect()->to(base_url('announcements'))->with('success', 'Announcement published.');
    }

    public function edit(int $id): string|RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to edit announcements.');
        }
        $ann = $this->announcements->find($id);
        if (! $ann) {
            return redirect()->to(base_url('announcements'))->with('error', 'Announcement not found.');
        }
        $data = [
            'announcement' => $ann,
            'role'         => session()->get('role') ?? 'ADMIN',
            'name'         => session()->get('name') ?? 'User',
        ];
        return view('Announcements/edit', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $ann = $this->announcements->find($id);
        if (! $ann) {
            return redirect()->to(base_url('announcements'))->with('error', 'Announcement not found.');
        }
        $title = trim((string) $this->request->getPost('title'));
        $body  = trim((string) $this->request->getPost('body'));
        if ($title === '' || $body === '') {
            return redirect()->back()->withInput()->with('error', 'Title and body are required.');
        }
        $this->announcements->update($id, ['title' => $title, 'body' => $body]);
        return redirect()->to(base_url('announcements'))->with('success', 'Announcement updated.');
    }

    public function delete(int $id): RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to delete announcements.');
        }
        $ann = $this->announcements->find($id);
        if (! $ann) {
            return redirect()->to(base_url('announcements'))->with('error', 'Announcement not found.');
        }
        $this->announcements->delete($id);
        return redirect()->to(base_url('announcements'))->with('success', 'Announcement deleted.');
    }

    private function canManageAnnouncements(): bool
    {
        $role = session()->get('role');
        return in_array($role, ['SUPER_ADMIN', 'ADMIN', 'PRINCIPAL', 'VICE_PRINCIPAL', 'HEAD_TEACHER', 'ANNOUNCER', 'TEACHER'], true);
    }

    private function audienceOptionsForRole(string $role): array
    {
        if (! in_array($role, ['PRINCIPAL', 'VICE_PRINCIPAL', 'HEAD_TEACHER', 'ANNOUNCER', 'ADMIN', 'SUPER_ADMIN'], true)) {
            return [];
        }

        return [
            'school-wide'   => 'For all users',
            'teachers-only' => 'For teachers only',
            'staff-only'    => 'For staffs only',
            'students-only' => 'For students only',
        ];
    }

    private function resolveRecipientsByAudience(string $audienceType, string $actorRole): array
    {
        $db = \Config\Database::connect();

        $fetchByRoles = static function (array $roles) use ($db): array {
            if ($roles === []) {
                return [];
            }
            return $db->table('users')
                ->select('id')
                ->where('is_active', 1)
                ->whereIn('role', $roles)
                ->get()
                ->getResultArray();
        };

        if ($audienceType === 'teachers-only') {
            $rows = $fetchByRoles(['TEACHER']);
            // Special rule: when Announcer sends to teachers, Principal should also receive.
            if ($actorRole === 'ANNOUNCER') {
                $rows = array_merge($rows, $fetchByRoles(['PRINCIPAL']));
            }
            return $this->uniqueRecipientRows($rows);
        }

        if ($audienceType === 'staff-only') {
            return $fetchByRoles(['TEACHER', 'GUIDANCE', 'PRINCIPAL', 'VICE_PRINCIPAL', 'HEAD_TEACHER', 'ANNOUNCER', 'ADMIN', 'SUPER_ADMIN']);
        }

        if ($audienceType === 'students-only') {
            $studentRows = $fetchByRoles(['STUDENT']);
            $rows = $studentRows;

            // Special rule: student-only announcements should also be visible to adviser teachers.
            // Applied for Principal and Announcer broadcasts.
            if (in_array($actorRole, ['ANNOUNCER', 'PRINCIPAL'], true)) {
                $sectionIds = $db->table('users')
                    ->select('section_id')
                    ->where('role', 'STUDENT')
                    ->where('is_active', 1)
                    ->where('section_id IS NOT NULL', null, false)
                    ->where('section_id !=', 0)
                    ->groupBy('section_id')
                    ->get()
                    ->getResultArray();

                $sectionIds = array_values(array_unique(array_map(static fn(array $r): int => (int) ($r['section_id'] ?? 0), $sectionIds)));
                $sectionIds = array_values(array_filter($sectionIds, static fn(int $id): bool => $id > 0));

                if ($sectionIds !== []) {
                    $adviserRows = $db->table('teacher_sections')
                        ->select('teacher_id AS id')
                        ->where('status', 'accepted')
                        ->whereIn('section_id', $sectionIds)
                        ->groupStart()
                            ->where('assignment_role', 'ADVISER')
                            ->orWhere('assignment_role', null)
                        ->groupEnd()
                        ->get()
                        ->getResultArray();
                    $rows = array_merge($rows, $adviserRows);
                }
            }

            return $this->uniqueRecipientRows($rows);
        }

        // Default: school-wide
        return $db->table('users')
            ->select('id')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();
    }

    private function uniqueRecipientRows(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $map[$id] = ['id' => $id];
            }
        }
        return array_values($map);
    }
}
