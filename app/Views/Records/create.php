<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Record - AJES</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        label { display: block; margin-bottom: 6px; }
        input[type="number"], input[type="text"], textarea { width: 100%; padding: 8px; margin-bottom: 12px; }
        button { padding: 8px 16px; }
        .message { margin-bottom: 10px; color: red; }
    </style>
</head>
<body>
    <h1>Create Record</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form action="<?= base_url('records/store') ?>" method="post">
        <?= csrf_field() ?>
        <label for="student_id">Student ID</label>
        <input type="number" id="student_id" name="student_id" required value="<?= esc(old('student_id')) ?>">

        <label for="type">Type</label>
        <input type="text" id="type" name="type" required value="<?= esc(old('type')) ?>">

        <label for="details">Details</label>
        <textarea id="details" name="details" rows="4" required><?= esc(old('details')) ?></textarea>

        <button type="submit">Save</button>
        <a href="<?= base_url('records') ?>">Cancel</a>
    </form>
</body>
</html>

