<?php

namespace App\Controllers;

use App\Libraries\AcademicYearManager;
use App\Libraries\GradeLevel;
use App\Models\AcademicYearModel;
use CodeIgniter\HTTP\RedirectResponse;

class AcademicYears extends BaseController
{
    protected AcademicYearModel $years;

    public function __construct()
    {
        $this->years = new AcademicYearModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $active = AcademicYearManager::getActive();
        $list   = $this->years->orderBy('label', 'DESC')->findAll();

        foreach ($list as &$row) {
            $id = (int) ($row['id'] ?? 0);
            $row['enrollment_count'] = AcademicYearManager::countEnrollmentsForYear($id);
            $st = (string) ($row['status'] ?? 'planning');
            $row['status_label'] = match ($st) {
                'active'   => 'Active',
                'closed'   => 'Closed',
                'planning' => 'Inactive',
                default    => ucfirst($st),
            };
        }
        unset($row);

        return view('Admin/AcademicYears/index', $this->ayShell([
            'ay_tab'         => 'overview',
            'ay_page_title'  => 'Academic Years',
            'years'          => $list,
            'sidebar_years'  => $list,
            'active_year'    => $active,
        ]));
    }

    public function store(): RedirectResponse
    {
        $label  = trim((string) $this->request->getPost('label'));
        $start  = trim((string) $this->request->getPost('start_date'));
        $end    = trim((string) $this->request->getPost('end_date'));
        $status = trim((string) $this->request->getPost('status'));
        if ($status === 'active') {
            $result = AcademicYearManager::createYear($label, $start, $end, 'planning');
            if ($result['ok'] && isset($result['id'])) {
                $activate = AcademicYearManager::setActive((int) $result['id']);
                return redirect()->to(base_url('admin/academic-years'))
                    ->with($activate['ok'] ? 'success' : 'error', $activate['message']);
            }
        } else {
            $result = AcademicYearManager::createYear($label, $start, $end, 'planning');
        }

        return redirect()->to(base_url('admin/academic-years'))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function activate(int $id): RedirectResponse
    {
        $result = AcademicYearManager::setActive($id);

        return redirect()->to(base_url('admin/academic-years'))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function toggle(int $id): RedirectResponse
    {
        $year = $this->years->find($id);
        if ($year === null) {
            return redirect()->to(base_url('admin/academic-years'))->with('error', 'Academic year not found.');
        }

        $status = (string) ($year['status'] ?? 'planning');
        if ($status === 'closed') {
            return redirect()->to(base_url('admin/academic-years'))
                ->with('error', 'Closed years are locked. Use Close & promote only at end of school year.');
        }

        $result = $status === 'active'
            ? AcademicYearManager::setInactive($id)
            : AcademicYearManager::setActive($id);

        return redirect()->to(base_url('admin/academic-years'))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * @return string|RedirectResponse
     */
    public function closeWizard()
    {
        $active = AcademicYearManager::getActive();
        if ($active === null) {
            return redirect()->to(base_url('admin/academic-years'))
                ->with('error', 'No active academic year. Create and activate one first.');
        }

        $retained = array_map('intval', (array) $this->request->getPost('retained'));
        $preview  = AcademicYearManager::previewEndOfYear((int) $active['id'], $retained);

        return view('Admin/AcademicYears/close_wizard', $this->ayShell([
            'ay_tab'          => 'close',
            'ay_page_title'   => 'End school year',
            'active_year'     => $active,
            'preview'         => $preview,
            'retained_ids'    => $retained,
            'next_label'      => trim((string) $this->request->getPost('next_label')) ?: $preview['next_label'],
            'clone_sections'  => (int) $this->request->getPost('clone_sections') === 1,
        ]));
    }

    public function executeClose(): RedirectResponse
    {
        $active = AcademicYearManager::getActive();
        if ($active === null) {
            return redirect()->to(base_url('admin/academic-years'))->with('error', 'No active academic year.');
        }

        if (trim((string) $this->request->getPost('confirm')) !== 'YES') {
            return redirect()->to(base_url('admin/academic-years/close'))
                ->with('error', 'Type YES to confirm end-of-year promotion.');
        }

        $retained     = array_map('intval', (array) $this->request->getPost('retained'));
        $nextLabel    = trim((string) $this->request->getPost('next_label'));
        $cloneSections = (int) $this->request->getPost('clone_sections') === 1;
        $actorId      = (int) (session()->get('user_id') ?? 0);

        if ($nextLabel === '') {
            $nextLabel = AcademicYearManager::suggestNextLabel($active['label'] ?? null);
        }

        $result = AcademicYearManager::executeEndOfYear(
            (int) $active['id'],
            $nextLabel,
            $retained,
            $actorId,
            $cloneSections
        );

        return redirect()->to(base_url('admin/academic-years'))
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * @return string|RedirectResponse
     */
    public function history(int $id)
    {
        $year = $this->years->find($id);
        if ($year === null) {
            return redirect()->to(base_url('admin/academic-years'))->with('error', 'Academic year not found.');
        }

        $perPage = 10;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset  = ($page - 1) * $perPage;
        $total   = AcademicYearManager::countEnrollmentsForYear($id);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $rows = AcademicYearManager::enrollmentsForYear($id, $perPage, $offset);
        foreach ($rows as &$r) {
            $r['grade_label'] = GradeLevel::label($r['grade_level'] ?? '');
            $r['outcome_label'] = ucfirst((string) ($r['outcome'] ?? 'enrolled'));
            $r['section_display'] = AcademicYearManager::enrollmentSectionLabel($r);
        }
        unset($r);

        return view('Admin/AcademicYears/history', $this->ayShell([
            'ay_tab'         => 'records',
            'ay_page_title'  => 'Enrollment records — ' . ($year['label'] ?? ''),
            'active_year'    => AcademicYearManager::getActive(),
            'history_year'   => $year,
            'year'           => $year,
            'enrollments'    => $rows,
            'current_page'   => $page,
            'per_page'       => $perPage,
            'total_rows'     => $total,
            'total_pages'    => $totalPages,
        ]));
    }

    /**
     * @return string|RedirectResponse
     */
    public function studentHistory(int $userId)
    {
        $user = model(\App\Models\UserModel::class)->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('admin/students-log'))->with('error', 'Student not found.');
        }

        $history = AcademicYearManager::enrollmentHistory($userId);
        foreach ($history as &$h) {
            $h['grade_label'] = GradeLevel::label($h['grade_level'] ?? '');
            $subs = $h['subjects_snapshot'] ?? null;
            $h['subjects_list'] = [];
            if (is_string($subs) && $subs !== '') {
                $decoded = json_decode($subs, true);
                if (is_array($decoded) && isset($decoded['slots'])) {
                    foreach ($decoded['slots'] as $slot) {
                        $s = trim((string) ($slot['subject'] ?? ''));
                        if ($s !== '') {
                            $h['subjects_list'][] = $s;
                        }
                    }
                }
            }
        }
        unset($h);

        return view('Admin/AcademicYears/student_history', $this->ayShell([
            'ay_tab'        => 'records',
            'ay_page_title' => 'Student enrollment history',
            'student'       => $user,
            'history'       => $history,
        ]));
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function ayShell(array $extra = []): array
    {
        return array_merge([
            'role'        => session()->get('role') ?? 'ADMIN',
            'name'        => session()->get('name') ?? 'User',
            'active_year' => AcademicYearManager::getActive(),
        ], $extra);
    }
}
