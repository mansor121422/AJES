<?php

namespace App\Controllers;

use App\Models\RecordModel;
use CodeIgniter\HTTP\RedirectResponse;

class Records extends BaseController
{
    protected RecordModel $records;

    public function __construct()
    {
        $this->records = new RecordModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $page     = max(1, (int) (service('request')->getGet('page') ?? 1));
        $perPage  = 10;
        $keyword  = trim((string) service('request')->getGet('q'));

        $builder = $this->records;

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('type', $keyword)
                ->orLike('details', $keyword)
                ->groupEnd();
        }

        $data = [
            'records' => $builder->orderBy('created_at', 'DESC')->paginate($perPage, 'default', $page),
            'pager'   => $builder->pager,
            'keyword' => $keyword,
        ];

        return view('Records/index', $data);
    }

    public function create(): string
    {
        return view('Records/create');
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

        return view('Records/edit', ['record' => $record]);
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

