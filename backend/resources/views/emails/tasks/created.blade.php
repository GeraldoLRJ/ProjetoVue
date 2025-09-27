<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <p>Olá {{ $task->user->name ?? 'Usuário' }},</p>

    <p>Uma nova tarefa foi criada para você:</p>

    <ul>
        <li><strong>Título:</strong> {{ $task->title }}</li>
        <li><strong>Descrição:</strong> {{ $task->description }}</li>
        <li><strong>Prazo:</strong> {{ $task->due_date ?? '—' }}</li>
        <li><strong>Prioridade:</strong> {{ $task->priority ?? '—' }}</li>
    </ul>

    <p>Boa sorte!</p>
</body>
</html>