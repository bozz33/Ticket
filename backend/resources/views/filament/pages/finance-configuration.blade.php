<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600">
                    Tarification, remboursements et reversements
                </p>
                <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    Une politique globale simple pilote désormais les ventes
                </h2>
                <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    La plateforme applique un taux de commission organisateur configurable et, si vous le souhaitez,
                    un supplément carte configurable par ticket. Si les champs sont laissés vides, aucun frais ni aucune
                    commission ne sont appliqués. Les frais gateway réels restent absorbés par la plateforme.
                </p>
            </div>

            <div class="mt-6 grid gap-4 rounded-2xl border border-primary-200 bg-primary-50/70 p-5 dark:border-primary-500/20 dark:bg-primary-500/10 md:grid-cols-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">
                        Commission active
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                        {{ (float) ($summary['commission_rate'] ?? 0) > 0 ? number_format((float) $summary['commission_rate'], 2, ',', ' ') . ' %' : '0 %' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Retenue appliquée sur le reversement organisateur.
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">
                        Frais carte / ticket
                    </p>
                    <p class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ (int) ($summary['card_fee_per_ticket'] ?? 0) > 0 ? number_format((int) $summary['card_fee_per_ticket'], 0, ',', ' ') . ' FCFA' : '0 FCFA' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Appliqués uniquement sur les paiements carte, par ticket.
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">
                        Politique remboursement carte
                    </p>
                    <p class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                        Technique / doublon uniquement
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Les frais carte ne sont pas remboursés, sauf erreur technique confirmée ou débit en doublon.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($cards as $card)
                    <a
                        href="{{ $card['url'] }}"
                        class="group flex h-full flex-col rounded-2xl border border-gray-200 bg-gray-50 p-5 transition hover:-translate-y-0.5 hover:border-primary-300 hover:bg-white hover:shadow-sm dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/60 dark:hover:bg-white/10"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-gray-950 group-hover:text-primary-700 dark:text-white dark:group-hover:text-primary-300">
                                    {{ $card['title'] }}
                                </h3>
                                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    {{ $card['description'] }}
                                </p>
                            </div>
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-200">
                                <x-heroicon-o-arrow-up-right class="h-5 w-5" />
                            </span>
                        </div>

                        @if (! empty($card['stats']))
                            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                @foreach ($card['stats'] as $label => $value)
                                    <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950/40">
                                        <dt class="text-[11px] font-medium uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                            {{ $label }}
                                        </dt>
                                        <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                                            {{ number_format((float) $value, 0, ',', ' ') }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600">
                Lecture rapide
            </p>

            <div class="mt-4 space-y-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="font-semibold text-gray-950 dark:text-white">1. Commission organisateur</p>
                    <p class="mt-1">Le taux configuré est retenu sur le sous-total pour calculer le net organisateur.</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="font-semibold text-gray-950 dark:text-white">2. Supplément carte</p>
                    <p class="mt-1">Le supplément carte est calculé par ticket et ne s’applique pas au mobile money.</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="font-semibold text-gray-950 dark:text-white">3. Frais gateway absorbés</p>
                    <p class="mt-1">Les frais Paystack restent des coûts internes pour la plateforme et ne se configurent plus ici.</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="font-semibold text-gray-950 dark:text-white">4. Reversement organisateur</p>
                    <p class="mt-1">Le net versé dépend uniquement de votre commission configurée et des politiques de reversement actives.</p>
                </div>
            </div>
        </aside>
    </div>
</x-filament-panels::page>
