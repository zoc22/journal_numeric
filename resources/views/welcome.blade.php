<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Users</title>
</head>
<body>

<h1>Liste des Users</h1>

<a href="{{ route('create.form') }}">
    <button>Ajouter +</button>
</a>

<table border="1" cellpadding="8">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Age</th>
            <th>Sexe</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>

    @forelse ($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->age }}</td>
            <td>{{ $user->sex }}</td>
            <td>

                {{-- Bouton modifier (optionnel) --}}
                <button>Modifier</button>

                {{-- Bouton supprimer --}}
                <form action="{{ route('delete.destroy', $user->id) }}"
                      method="POST"
                      style="display:inline">

                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        onclick="return confirm('Supprimer cet utilisateur ?')">
                        Supprimer
                    </button>

                </form>

            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5">Aucun utilisateur trouvé</td>
        </tr>
    @endforelse

    </tbody>
</table>

</body>
</html>
