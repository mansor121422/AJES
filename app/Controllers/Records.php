<?php

namespace App\Controllers;

use App\Models\RecordModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Records extends BaseController
{
    protected RecordModel $records;
    protected UserModel $users;

    public function __construct()
    {
        $this->records = new RecordModel();
        $this->users   = new UserModel();
        helper(['url', 'form', 'text']);
    }

    public function index(): string
    {
        $page       = max(1, (int) (service('request')->getGet('page') ?? 1));
        $perPage    = 10;
        $keyword    = trim((string) service('request')->getGet('q'));
        $typeFilter = trim((string) service('request')->getGet('type'));

        $db         = \Config\Database::connect();
        $typeRows   = $db->table('records')->select('type')->distinct()->orderBy('type')->get()->getResultArray();
        $typeList   = array_column($typeRows, 'type');

        $builder = $this->records;

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('type', $keyword)
                ->orLike('details', $keyword)
                ->groupEnd();
        }

        if ($typeFilter !== '') {
            $builder = $builder->where('type', $typeFilter);
        }

        $data = [
            'records'     => $builder->orderBy('created_at', 'DESC')->paginate($perPage, 'default', $page),
            'pager'       => $builder->pager,
            'keyword'     => $keyword,
            'typeFilter'  => $typeFilter,
            'typeList'    => $typeList,
            'recordTypes' => $this->recordTypes(),
            'role'        => session()->get('role') ?? 'ADMIN',
            'name'        => session()->get('name') ?? 'User',
        ];
        return view('Records/index', $data);
    }

    /** Record types for counseling/session notes (used in forms). */
    protected function recordTypes(): array
    {
        return ['Session', 'Note', 'Referral', 'Other'];
    }

    public function create(): string
    {
        $students = $this->users->where('role', 'STUDENT')->orderBy('name')->findAll();
        $data = [
            'students'    => $students,
            'recordTypes' => $this->recordTypes(),
            'role'        => session()->get('role') ?? 'ADMIN',
            'name'        => session()->get('name') ?? 'User',
        ];
        return view('Records/create', $data);
    }

    public function store(): RedirectResponse
    {
        $request = service('request');
        $session = session();

        $studentId = (int) $request->getPost('student_id');
        $type      = trim((string) $request->getPost('type'));
        $details   = trim((string) $request->getPost('details'));

        if ($studentId <= 0 || $type === '' || $details === '') {
            return redirect()->back()->withInput()->with('error', 'All fields are required.');
        }
        if (strlen($type) > 50) {
            return redirect()->back()->withInput()->with('error', 'Type must be at most 50 characters.');
        }
        if (strlen($details) > 10000) {
            return redirect()->back()->withInput()->with('error', 'Details must be at most 10,000 characters.');
        }

        $this->records->insert([
            'student_id' => $studentId,
            'type'       => $type,
            'details'    => $details,
            'created_by' => $session->get('user_id'),
        ]);

        return redirect()->to('records')->with('success', 'Record created.');
    }

    public function edit(int $id): string
    {
        $record = $this->records->find($id);

        if (! $record) {
            return redirect()->to('records')->with('error', 'Record not found.');
        }

        $students = $this->users->where('role', 'STUDENT')->orderBy('name')->findAll();
        $data = [
            'record'      => $record,
            'students'    => $students,
            'recordTypes' => $this->recordTypes(),
            'role'        => session()->get('role') ?? 'ADMIN',
            'name'        => session()->get('name') ?? 'User',
        ];
        return view('Records/edit', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $request = service('request');

        $studentId = (int) $request->getPost('student_id');
        $type      = trim((string) $request->getPost('type'));
        $details   = trim((string) $request->getPost('details'));

        if ($studentId <= 0 || $type === '' || $details === '') {
            return redirect()->back()->withInput()->with('error', 'All fields are required.');
        }
        if (strlen($type) > 50) {
            return redirect()->back()->withInput()->with('error', 'Type must be at most 50 characters.');
        }
        if (strlen($details) > 10000) {
            return redirect()->back()->withInput()->with('error', 'Details must be at most 10,000 characters.');
        }

        $this->records->update($id, [
            'student_id' => $studentId,
            'type'       => $type,
            'details'    => $details,
        ]);

        return redirect()->to('records')->with('success', 'Record updated.');
    }

    public function delete(int $id): RedirectResponse
    {
        $this->records->delete($id);

        return redirect()->to('records')->with('success', 'Record deleted.');
    }
}

