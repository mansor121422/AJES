<?php

namespace App\Controllers;

class Chat extends BaseController
{
    public function __construct()
    {
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $data = [
            'role' => session()->get('role') ?? 'ADMIN',
            'name' => session()->get('name') ?? 'User',
        ];
        return view('Chat/index', $data);
    }
}
