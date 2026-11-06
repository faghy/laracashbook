<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Cestino</h2>
        <x-entity-choice :entities="$entities" />
    </x-slot>

    <p class="mb-4 text-slate-600">I movimenti eliminati restano qui finché non li ripristini o li elimini definitivamente.</p>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Descrizione</th>
                    <th scope="col">Categoria</th>
                    <th scope="col" class="text-right">Importo</th>
                    <th scope="col">Eliminato il</th>
                    <th scope="col" class="text-right"><span class="sr-only">Azioni</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($movs as $mov)
                <tr>
                    <td class="whitespace-nowrap tabular-nums text-slate-600">{{ $mov->data_movimento->format('d/m/Y') }}</td>
                    <td class="font-medium">{{ $mov->descrizione }}</td>
                    <td><span class="badge bg-slate-100 text-slate-700 text-sm font-medium">{{ optional($mov->categorie)->descriz_cat ?? 'N/D' }}</span></td>
                    <td class="text-right"><span class="amount {{ $mov->entratauscita ? 'text-emerald-700' : 'text-rose-700' }}">@euro($mov->importo)</span></td>
                    <td class="whitespace-nowrap tabular-nums text-slate-600">{{ $mov->deleted_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="flex justify-end gap-1">
                            <form method="POST" action="{{ route('movimenti.restore', $mov->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-secondary">Ripristina</button>
                            </form>
                            <form method="POST" action="{{ route('movimenti.forceDestroy', $mov->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        data-confirm="Eliminare definitivamente il movimento {{ $mov->descrizione }}? L'operazione non si può annullare."
                                        onclick="return confirm(this.dataset.confirm)"
                                        class="btn btn-sm btn-ghost-danger">Elimina definitivamente</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-10 text-center text-slate-500">Il cestino è vuoto.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $movs->links() }}</div>
</x-app-layout>
