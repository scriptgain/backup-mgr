@php
    $fmt = function ($b) {
        if (! $b) return '0 B';
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($b, 1024));
        return round($b / (1024 ** $i), 1) . ' ' . $u[$i];
    };
@endphp
<x-layouts.app title="Storage & Disks">
    <x-page-header title="Storage & Disks" icon="cloud" subtitle="Disks detected across your directors." />


    <div class="space-y-6">
        @foreach ($directors as $director)
            <x-card>
                <x-slot:title>{{ $director->name }}</x-slot:title>
                <x-slot:actions>
                    <form method="POST" action="{{ route('directors.storage.detect', $director) }}">
                        @csrf
                        <x-button variant="secondary" size="sm" icon="refresh" type="submit">Detect Disks</x-button>
                    </form>
                </x-slot:actions>

                @if ($director->storageDevices->isEmpty())
                    <p class="text-sm text-slate-500 py-2">No disks recorded. Click <span class="font-medium">Detect Disks</span> to scan.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($director->storageDevices as $d)
                            @php
                                $total = (int) $d->total_bytes;
                                $used = (int) $d->used_bytes;
                                $pct = $total > 0 ? min(100, round($used / $total * 100)) : 0;
                                $bar = $pct >= 90 ? 'bg-rose-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-brand-500');
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <div>
                                        <span class="font-medium text-slate-900">{{ $d->name }}</span>
                                        <span class="font-mono text-xs text-slate-400 ml-2">{{ $d->mount_path }}</span>
                                    </div>
                                    <span class="text-slate-500">{{ $fmt($used) }} / {{ $fmt($total) }} ({{ $pct }}%)</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full {{ $bar }} rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>
</x-layouts.app>
