<div class="overflow-x-auto">
    <table class="w-full min-w-[68rem] border-separate border-spacing-0 text-left">
        <thead>
            <tr class="bg-stone-50 text-[0.68rem] font-extrabold uppercase tracking-[0.12em] text-stone-500">
                <th scope="col" class="rounded-l-xl border-y border-l border-stone-200 px-5 py-3.5">Data</th>
                <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Horário</th>
                <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Beneficiário</th>
                <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Tipo de ação</th>
                <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Responsável</th>
                <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Prioridade</th>
                <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Status</th>
                <th scope="col" class="rounded-r-xl border-y border-r border-stone-200 px-5 py-3.5"><span class="sr-only">Ações</span></th>
            </tr>
        </thead>

        <tbody class="divide-y divide-stone-100">
            @forelse($listaAgendamentos as $agendamento)
                <tr class="group transition hover:bg-brand-50/55">
                    <td class="p-0 text-sm font-bold text-stone-800"><a href="{{ route('agendamento.show', $agendamento) }}" class="block whitespace-nowrap px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" data-detail-link="agendamento" title="Ver detalhes do agendamento">{{ $agendamento->data_agendada->format('d/m/Y') }}</a></td>
                    <td class="p-0 text-sm text-stone-600"><a href="{{ route('agendamento.show', $agendamento) }}" class="block whitespace-nowrap px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" data-detail-link="agendamento" title="Ver detalhes do agendamento">{{ substr($agendamento->hora_agendada, 0, 5) }}</a></td>
                    <td class="p-0 text-sm"><a href="{{ route('agendamento.show', $agendamento) }}" class="block max-w-52 truncate px-5 py-4 font-bold text-brand-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" data-detail-link="agendamento" title="Ver detalhes de {{ $agendamento->beneficiario->nome_beneficiario ?? 'beneficiário' }}">{{ $agendamento->beneficiario->nome_beneficiario ?? 'Não informado' }}</a></td>
                    <td class="p-0 text-sm text-stone-600"><a href="{{ route('agendamento.show', $agendamento) }}" class="block max-w-44 truncate px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" data-detail-link="agendamento" title="{{ $agendamento->tipo_acao }}">{{ $agendamento->tipo_acao }}</a></td>
                    <td class="p-0 text-sm text-stone-600"><a href="{{ route('agendamento.show', $agendamento) }}" class="block max-w-44 truncate px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" data-detail-link="agendamento" title="{{ $agendamento->responsavel->name ?? 'Não definido' }}">{{ $agendamento->responsavel->name ?? 'Não definido' }}</a></td>
                    <td class="p-0">
                        <a href="{{ route('agendamento.show', $agendamento) }}" class="block px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" data-detail-link="agendamento" title="Ver detalhes do agendamento">
                            <x-ui.badge type="priority" :value="$agendamento->prioridade" />
                        </a>
                    </td>
                    <td class="p-0">
                        <a href="{{ route('agendamento.show', $agendamento) }}" class="block px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" data-detail-link="agendamento" title="Ver detalhes do agendamento">
                            <x-ui.badge type="status" :value="$agendamento->status" />
                        </a>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('agendamento.show', $agendamento) }}" class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-white hover:text-brand-800 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-brand-400/20" title="Ver agendamento" aria-label="Ver agendamento"><i data-lucide="eye" class="size-4" aria-hidden="true"></i></a>
                            @if(auth()->user()->role === 'lider' && in_array($agendamento->status, ['agendado', 'reagendado'], true))
                                <a href="{{ route('agendamento.edit', $agendamento) }}" class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-white hover:text-brand-800 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-brand-400/20" title="Editar agendamento" aria-label="Editar agendamento"><i data-lucide="pencil" class="size-4" aria-hidden="true"></i></a>
                            @endif
                            @if(in_array(auth()->user()->role, ['lider', 'voluntario'], true) && in_array($agendamento->status, ['agendado', 'reagendado'], true))
                                <a href="{{ route('historico-acoes.create', ['agendamento' => $agendamento->getKey()]) }}" class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-white hover:text-emerald-700 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-400/20" title="Registrar relatório" aria-label="Registrar relatório"><i data-lucide="clipboard-plus" class="size-4" aria-hidden="true"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-0">
                        <x-ui.empty-state title="Nenhuma ação encontrada" :description="$mensagemVazia" icon="calendar-x" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($listaAgendamentos->hasPages())
    <div class="border-t border-stone-100 px-5 py-4">
        {{ $listaAgendamentos->withQueryString()->links() }}
    </div>
@endif
