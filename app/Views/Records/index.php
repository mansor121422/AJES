<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Records - AJES</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .message { margin-bottom: 10px; color: red; }
        .success { color: green; }
        a { text-decoration: none; }
    </style>
</head>
<body>
    <h1>Records</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <form method="get" action="">
        <input type="text" name="q" placeholder="Search..." value="<?= esc($keyword) ?>">
        <button type="submit">Search</button>
        <a href="<?= base_url('records/create') ?>">Create Record</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student ID</th>
                <th>Type</th>
                <th>Details</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="6">No records found.</td></tr>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= esc($record['id']) ?></td>
                        <td><?= esc($record['student_id']) ?></td>
                        <td><?= esc($record['type']) ?></td>
                        <td><?= esc($record['details']) ?></td>
                        <td><?= esc($record['created_at']) ?></td>
                        <td>
                            <a href="<?= base_url('records/edit/' . $record['id']) ?>">Edit</a>
                            |
                            <a href="<?= base_url('records/delete/' . $record['id']) ?>" onclick="return confirm('Delete this record?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

