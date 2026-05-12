<x-filament-panels::page>
    <div
        x-data="ticketAccessPassScanner($wire)"
        class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)]"
    >
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="grid gap-4">
                <div class="grid gap-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">
                        Contrôle d'accès
                    </p>
                    <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        Scanner ou vérifier un pass
                    </h2>
                    <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Cette page permet de lire un QR code ou de coller un code d'accès, puis de prévisualiser, valider
                        l'entrée ou réinitialiser un pass si besoin. Elle fonctionne pour les billets événement, les
                        inscriptions formation, les réservations stand et les pass liés à un achat.
                    </p>
                </div>

                <div class="grid gap-4 rounded-2xl border border-dashed border-gray-300 bg-gray-50/80 p-4 dark:border-white/15 dark:bg-white/[0.03]">
                    <div class="flex flex-wrap gap-3">
                        <x-filament::button
                            type="button"
                            x-on:click="startScanner()"
                            x-bind:disabled="busy || active"
                            icon="heroicon-m-camera"
                        >
                            Activer la caméra
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            type="button"
                            x-on:click="stopScanner()"
                            x-bind:disabled="! active"
                            icon="heroicon-m-stop-circle"
                        >
                            Arrêter
                        </x-filament::button>
                    </div>

                    <div class="grid gap-3 rounded-2xl bg-gray-950 p-3 text-white shadow-inner">
                        <div
                            class="relative overflow-hidden rounded-2xl border border-white/10 bg-black/70"
                            style="aspect-ratio: 16 / 10;"
                        >
                            <video
                                x-ref="video"
                                x-show="active"
                                autoplay
                                class="h-full w-full object-cover"
                                muted
                                playsinline
                            ></video>
                            <div
                                x-show="! active"
                                class="absolute inset-0 grid place-items-center px-6 text-center text-sm text-white/65"
                            >
                                Ouvrez la caméra pour scanner un QR code ou utilisez le champ manuel juste en dessous.
                            </div>
                            <div class="pointer-events-none absolute inset-[12%] rounded-[28px] border border-primary-400/70 shadow-[0_0_0_9999px_rgba(3,7,18,0.26)]"></div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-xs text-white/70">
                            <span class="inline-flex rounded-full border border-white/10 bg-white/10 px-2.5 py-1">
                                QR caméra
                            </span>
                            <span x-show="! supported">
                                Votre navigateur ne prend pas en charge le scan natif. Le contrôle manuel reste disponible.
                            </span>
                            <span x-show="supported && ! active">
                                Le scan s'arrête automatiquement dès qu'un code valide est détecté.
                            </span>
                            <span x-show="active">
                                Caméra active. Présentez le QR bien face à l'écran.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.02]">
                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Code, identifiant public ou contenu QR</span>
                        <textarea
                            x-ref="identifier"
                            wire:model.live="identifier"
                            class="min-h-[108px] rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-950 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                            placeholder="Collez ici le code d'accès, un public_id, un JSON QR ou même l'URL de vérification."
                        ></textarea>
                    </label>

                    <div class="flex flex-wrap gap-3">
                        <x-filament::button type="button" wire:click="preview" icon="heroicon-m-magnifying-glass">
                            Prévisualiser
                        </x-filament::button>
                        <x-filament::button type="button" color="success" wire:click="consume" icon="heroicon-m-check-circle">
                            Valider l'entrée
                        </x-filament::button>
                        <x-filament::button type="button" color="warning" wire:click="resetPass" icon="heroicon-m-arrow-path">
                            Réinitialiser
                        </x-filament::button>
                        <x-filament::button type="button" color="gray" wire:click="clearState" icon="heroicon-m-x-mark">
                            Effacer
                        </x-filament::button>
                    </div>
                </div>

                <div class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.02]">
                    <div class="grid gap-1">
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Modules pris en charge par QR</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            La vérification fonctionne partout où un pass d'accès existe réellement. Pour les modules qui ne
                            génèrent pas de pass, il n'y a rien à scanner.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($this->supportedTypes() as $label => $description)
                            <article class="rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $label }}</p>
                                <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-300">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <aside class="grid gap-6">
            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                            Résultat
                        </p>
                        <h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">
                            @if ($scanState)
                                {{ $scanState['result']['access_granted'] ? 'Accès accordé' : 'Contrôle requis' }}
                            @else
                                Aucun pass contrôlé
                            @endif
                        </h3>
                    </div>

                    @if ($scanState)
                        @php
                            $granted = (bool) ($scanState['result']['access_granted'] ?? false);
                            $statusClasses = $granted
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300';
                        @endphp
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                            {{ $scanState['result']['result_label'] ?? 'Statut' }}
                        </span>
                    @endif
                </div>

                @if ($scanState)
                    <div class="mt-5 grid gap-5">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/90 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ $scanState['result']['message'] ?? 'Contrôle terminé.' }}
                            </p>
                            <p class="mt-1 text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                                Action : {{ $scanState['action'] === 'consume' ? 'validation' : ($scanState['action'] === 'reset' ? 'réinitialisation' : 'prévisualisation') }}
                            </p>
                        </div>

                        <dl class="grid gap-3">
                            <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Code</dt>
                                <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $scanState['pass']['access_code'] }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Type</dt>
                                <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $scanState['pass']['type_label'] }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Titulaire</dt>
                                <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $scanState['pass']['holder_name'] }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">E-mail</dt>
                                <dd class="text-right text-sm text-gray-700 dark:text-gray-200">{{ $scanState['pass']['holder_email'] }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Commande</dt>
                                <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $scanState['pass']['order_reference'] }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Offre</dt>
                                <dd class="text-right text-sm text-gray-700 dark:text-gray-200">{{ $scanState['pass']['offer_title'] }}</dd>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Utilisé le</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $scanState['pass']['used_at'] }}</dd>
                                </div>
                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Expire le</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $scanState['pass']['expires_at'] }}</dd>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Nombre de scans</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $scanState['pass']['scan_count'] }}</dd>
                                </div>
                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Dernier scan</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $scanState['pass']['last_scan_at'] }}</dd>
                                </div>
                            </div>

                            @if ($scanState['pass']['revocation_reason'])
                                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                                    <strong class="font-semibold">Motif de révocation :</strong>
                                    {{ $scanState['pass']['revocation_reason'] }}
                                </div>
                            @endif
                        </dl>
                    </div>
                @else
                    <div class="mt-5 rounded-2xl border border-dashed border-gray-300 bg-gray-50/80 p-5 text-sm leading-6 text-gray-600 dark:border-white/15 dark:bg-white/[0.03] dark:text-gray-300">
                        Lancez un scan caméra ou collez un code d&apos;accès pour prévisualiser l&apos;état d&apos;un pass, valider
                        l&apos;entrée ou réinitialiser un accès déjà consommé.
                    </div>
                @endif
            </section>
        </aside>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('ticketAccessPassScanner', ($wire) => ({
                active: false,
                busy: false,
                supported: Boolean(window.BarcodeDetector && navigator.mediaDevices?.getUserMedia),
                detector: null,
                stream: null,
                rafId: null,
                lastValue: null,

                async startScanner() {
                    if (! this.supported || this.active || this.busy) {
                        return;
                    }

                    this.busy = true;

                    try {
                        this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: { ideal: 'environment' },
                            },
                            audio: false,
                        });

                        this.$refs.video.srcObject = this.stream;
                        await this.$refs.video.play();
                        this.active = true;
                        this.scanLoop();
                    } catch (error) {
                        console.error(error);
                        this.stopScanner();
                        this.supported = false;
                    } finally {
                        this.busy = false;
                    }
                },

                stopScanner() {
                    this.active = false;

                    if (this.rafId) {
                        window.cancelAnimationFrame(this.rafId);
                        this.rafId = null;
                    }

                    if (this.stream) {
                        this.stream.getTracks().forEach((track) => track.stop());
                        this.stream = null;
                    }

                    if (this.$refs.video) {
                        this.$refs.video.pause();
                        this.$refs.video.srcObject = null;
                    }
                },

                async scanLoop() {
                    if (! this.active || ! this.detector || ! this.$refs.video) {
                        return;
                    }

                    try {
                        const codes = await this.detector.detect(this.$refs.video);

                        if (codes.length > 0) {
                            const rawValue = String(codes[0]?.rawValue || '').trim();

                            if (rawValue && rawValue !== this.lastValue) {
                                this.lastValue = rawValue;
                                await $wire.set('identifier', rawValue);
                                await $wire.preview();
                                this.stopScanner();
                                return;
                            }
                        }
                    } catch (error) {
                        console.error(error);
                    }

                    this.rafId = window.requestAnimationFrame(() => this.scanLoop());
                },
            }));
        });
    </script>
</x-filament-panels::page>
