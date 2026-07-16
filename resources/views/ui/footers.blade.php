<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Footer Gallery</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#edf5ff', 100:'#d0e2ff', 200:'#a6c8ff', 300:'#78a9ff', 400:'#4589ff', 500:'#0f62fe', DEFAULT:'#0f62fe', 600:'#0353e9', 700:'#0043ce', 800:'#002d9c', 900:'#001d6c' },
                        navy: '#0b2545',
                        ink: '#0b2545'
                    },
                    fontFamily: {
                        sans: ['IBM Plex Sans', 'system-ui', 'sans-serif'],
                        mono: ['IBM Plex Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important}</style>
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased">
<div x-data="{ current: 0, total: 20, go(i){ this.current = ((i % this.total) + this.total) % this.total } }">

    {{-- Sticky control bar --}}
    <div class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-3">
            <div class="flex items-center gap-2">
                <svg class="h-6 w-6 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                </svg>
                <span class="text-sm font-semibold tracking-tight">Footer Gallery</span>
            </div>

            <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium tabular-nums text-slate-700">
                Footer <span x-text="current + 1"></span> / 20
            </span>

            <div class="ms-auto flex items-center gap-2">
                <button type="button" @click="go(current - 1)"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    Previous
                </button>
                <button type="button" @click="go(current + 1)"
                    class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    Next
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>

            <div class="flex w-full flex-wrap gap-1.5">
                <template x-for="n in total" :key="n">
                    <button type="button" @click="go(n - 1)"
                        :class="current === (n - 1) ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'"
                        class="h-8 w-8 rounded-md text-sm font-semibold tabular-nums ring-1 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                        x-text="n"></button>
                </template>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- DESIGN 01 - Classic multi-column dark, two-tone legal bar --}}
    {{-- =========================================================== --}}
    <section x-show="current === 0" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 01</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">Scroll down to view the footer. This block simulates real page content so the footer sits where it belongs.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-navy text-slate-300">
            <div class="mx-auto max-w-7xl px-6 py-16">
                <div class="grid gap-10 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <div class="text-xl font-bold text-white">ScriptGain</div>
                        <p class="mt-3 max-w-xs text-sm text-slate-400">Resilient backup infrastructure for teams that cannot afford to lose data.</p>
                        <div class="mt-6 flex gap-3">
                            <a href="#" aria-label="X" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                            <a href="#" aria-label="LinkedIn" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
                            <a href="#" aria-label="GitHub" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg></a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Overview</a></li>
                            <li><a href="#" class="transition hover:text-white">Agents</a></li>
                            <li><a href="#" class="transition hover:text-white">Repositories</a></li>
                            <li><a href="#" class="transition hover:text-white">Pricing</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Solutions</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Enterprise</a></li>
                            <li><a href="#" class="transition hover:text-white">Managed Hosting</a></li>
                            <li><a href="#" class="transition hover:text-white">Compliance</a></li>
                            <li><a href="#" class="transition hover:text-white">Disaster Recovery</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">About</a></li>
                            <li><a href="#" class="transition hover:text-white">Careers</a></li>
                            <li><a href="#" class="transition hover:text-white">Partners</a></li>
                            <li><a href="#" class="transition hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Resources</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Documentation</a></li>
                            <li><a href="#" class="transition hover:text-white">API Reference</a></li>
                            <li><a href="#" class="transition hover:text-white">Status</a></li>
                            <li><a href="#" class="transition hover:text-white">Changelog</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 bg-black/30">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6">
                        <a href="#" class="transition hover:text-white">Privacy</a>
                        <a href="#" class="transition hover:text-white">Terms</a>
                        <a href="#" class="transition hover:text-white">Cookies</a>
                    </div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 02 - Light, centered, minimal single link row --}}
    {{-- =========================================================== --}}
    <section x-show="current === 1" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 02</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">Scroll down to view the footer. A calm, centered, light layout.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-4xl px-6 py-16 text-center">
                <div class="text-2xl font-bold tracking-tight text-slate-900">ScriptGain</div>
                <p class="mx-auto mt-3 max-w-md text-sm text-slate-500">Automated, encrypted, and always recoverable.</p>
                <nav class="mt-8 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm font-medium text-slate-600">
                    <a href="#" class="transition hover:text-brand-600">Product</a>
                    <a href="#" class="transition hover:text-brand-600">Solutions</a>
                    <a href="#" class="transition hover:text-brand-600">Pricing</a>
                    <a href="#" class="transition hover:text-brand-600">Docs</a>
                    <a href="#" class="transition hover:text-brand-600">Company</a>
                    <a href="#" class="transition hover:text-brand-600">Contact</a>
                </nav>
                <div class="mt-8 flex justify-center gap-4">
                    <a href="#" aria-label="X" class="text-slate-400 transition hover:text-brand-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                    <a href="#" aria-label="LinkedIn" class="text-slate-400 transition hover:text-brand-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
                    <a href="#" aria-label="YouTube" class="text-slate-400 transition hover:text-brand-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814z"/></svg></a>
                </div>
                <div class="mt-10 border-t border-slate-200 pt-6 text-sm text-slate-500">
                    <p>&copy; 2026 ScriptGain. Privacy, Terms and Cookies.</p>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 03 - CTA band above + dark multi-column --}}
    {{-- =========================================================== --}}
    <section x-show="current === 2" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 03</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A conversion CTA band leads into the footer.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <div>
            <div class="bg-brand-600">
                <div class="mx-auto flex max-w-7xl flex-col items-start gap-6 px-6 py-12 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-white">Ready To Protect Your Data?</h2>
                        <p class="mt-2 text-brand-100">Deploy your first backup agent in under ten minutes.</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="#" class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">Start Free Trial</a>
                        <a href="#" class="rounded-lg border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">Book A Demo</a>
                    </div>
                </div>
            </div>
            <footer class="bg-navy text-slate-300">
                <div class="mx-auto grid max-w-7xl gap-10 px-6 py-14 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <div class="text-lg font-bold text-white">ScriptGain</div>
                        <p class="mt-3 text-sm text-slate-400">Enterprise backup, simplified.</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Features</a></li>
                            <li><a href="#" class="transition hover:text-white">Integrations</a></li>
                            <li><a href="#" class="transition hover:text-white">Pricing</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">About</a></li>
                            <li><a href="#" class="transition hover:text-white">Blog</a></li>
                            <li><a href="#" class="transition hover:text-white">Careers</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Legal</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Privacy</a></li>
                            <li><a href="#" class="transition hover:text-white">Terms</a></li>
                            <li><a href="#" class="transition hover:text-white">Cookies</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-white/10">
                    <div class="mx-auto max-w-7xl px-6 py-6 text-sm text-slate-400">
                        <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    </div>
                </div>
            </footer>
        </div>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 04 - Newsletter-first light footer --}}
    {{-- =========================================================== --}}
    <section x-show="current === 3" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 04</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A newsletter capture leads the footer, columns below.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-col gap-6 border-b border-slate-200 py-12 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">Join Our Newsletter</h2>
                        <p class="mt-2 text-sm text-slate-500">Product updates and recovery best practices. No spam.</p>
                    </div>
                    <form class="flex w-full max-w-md gap-3">
                        <label for="nl4" class="sr-only">Email Address</label>
                        <input id="nl4" type="email" placeholder="you@company.com" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                        <button type="submit" class="shrink-0 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Subscribe</button>
                    </form>
                </div>
                <div class="grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Overview</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Pricing</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Security</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Resources</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Docs</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Guides</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Support</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">About</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Press</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Legal</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Privacy</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Terms</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Cookies</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200 bg-slate-50">
                <div class="mx-auto max-w-7xl px-6 py-5 text-sm text-slate-500">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 05 - Minimal single-row light footer --}}
    {{-- =========================================================== --}}
    <section x-show="current === 4" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 05</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">The leanest possible footer, one row.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col items-center gap-4 px-6 py-6 sm:flex-row sm:justify-between">
                <div class="flex items-center gap-2">
                    <svg class="h-6 w-6 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                    <span class="font-semibold text-slate-900">ScriptGain</span>
                </div>
                <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm font-medium text-slate-600">
                    <a href="#" class="transition hover:text-brand-600">Product</a>
                    <a href="#" class="transition hover:text-brand-600">Pricing</a>
                    <a href="#" class="transition hover:text-brand-600">Docs</a>
                    <a href="#" class="transition hover:text-brand-600">Privacy</a>
                    <a href="#" class="transition hover:text-brand-600">Terms</a>
                    <a href="#" class="transition hover:text-brand-600">Cookies</a>
                </nav>
                <p class="text-sm text-slate-500">&copy; 2026 ScriptGain</p>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 06 - Dense sitemap grid, dark chrome --}}
    {{-- =========================================================== --}}
    <section x-show="current === 5" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 06</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A dense, sitemap-style footer with six link groups.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-navy text-slate-300">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-6">
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Product</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#" class="transition hover:text-white">Overview</a></li>
                            <li><a href="#" class="transition hover:text-white">Agents</a></li>
                            <li><a href="#" class="transition hover:text-white">Snapshots</a></li>
                            <li><a href="#" class="transition hover:text-white">Restore</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Solutions</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#" class="transition hover:text-white">Startups</a></li>
                            <li><a href="#" class="transition hover:text-white">Enterprise</a></li>
                            <li><a href="#" class="transition hover:text-white">Agencies</a></li>
                            <li><a href="#" class="transition hover:text-white">MSPs</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Developers</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#" class="transition hover:text-white">API</a></li>
                            <li><a href="#" class="transition hover:text-white">CLI</a></li>
                            <li><a href="#" class="transition hover:text-white">Webhooks</a></li>
                            <li><a href="#" class="transition hover:text-white">SDKs</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Resources</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#" class="transition hover:text-white">Docs</a></li>
                            <li><a href="#" class="transition hover:text-white">Blog</a></li>
                            <li><a href="#" class="transition hover:text-white">Guides</a></li>
                            <li><a href="#" class="transition hover:text-white">Community</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Company</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#" class="transition hover:text-white">About</a></li>
                            <li><a href="#" class="transition hover:text-white">Careers</a></li>
                            <li><a href="#" class="transition hover:text-white">Press</a></li>
                            <li><a href="#" class="transition hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Legal</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#" class="transition hover:text-white">Privacy</a></li>
                            <li><a href="#" class="transition hover:text-white">Terms</a></li>
                            <li><a href="#" class="transition hover:text-white">Cookies</a></li>
                            <li><a href="#" class="transition hover:text-white">Security</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-base font-bold text-white">ScriptGain</div>
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 07 - Two-tone dark, status + language selector --}}
    {{-- =========================================================== --}}
    <section x-show="current === 6" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 07</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">Includes a system status pill and a region selector.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-slate-900 text-slate-300">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid gap-10 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <div class="text-xl font-bold text-white">ScriptGain</div>
                        <p class="mt-3 max-w-xs text-sm text-slate-400">Continuous protection with instant, verified recovery.</p>
                        <a href="#" class="mt-6 inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-sm font-medium text-emerald-300">
                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75 motion-reduce:animate-none"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span></span>
                            All Systems Operational
                        </a>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Features</a></li>
                            <li><a href="#" class="transition hover:text-white">Pricing</a></li>
                            <li><a href="#" class="transition hover:text-white">Security</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">About</a></li>
                            <li><a href="#" class="transition hover:text-white">Careers</a></li>
                            <li><a href="#" class="transition hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Support</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Help Center</a></li>
                            <li><a href="#" class="transition hover:text-white">Status</a></li>
                            <li><a href="#" class="transition hover:text-white">Contact Sales</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 bg-slate-950">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex items-center gap-6">
                        <div class="flex gap-4">
                            <a href="#" class="transition hover:text-white">Privacy</a>
                            <a href="#" class="transition hover:text-white">Terms</a>
                            <a href="#" class="transition hover:text-white">Cookies</a>
                        </div>
                        <label class="sr-only" for="region7">Region</label>
                        <div class="inline-flex items-center gap-2 rounded-lg border border-white/15 px-3 py-1.5">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                            <select id="region7" class="bg-transparent text-slate-300 focus:outline-none"><option class="text-slate-900">United States</option><option class="text-slate-900">United Kingdom</option><option class="text-slate-900">Germany</option></select>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 08 - Light, left logo + compliance badges --}}
    {{-- =========================================================== --}}
    <section x-show="current === 7" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 08</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">Trust and compliance badges anchor this light footer.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid gap-10 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <div class="text-xl font-bold text-slate-900">ScriptGain</div>
                        <p class="mt-3 max-w-xs text-sm text-slate-500">Audited, encrypted, and compliant by design.</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>SOC 2</span>
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>ISO 27001</span>
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>GDPR</span>
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>HIPAA</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Overview</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Pricing</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Security</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">About</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Legal</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Privacy</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Terms</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Cookies</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200">
                <div class="mx-auto max-w-7xl px-6 py-6 text-sm text-slate-500">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 09 - Dark with app-store badges --}}
    {{-- =========================================================== --}}
    <section x-show="current === 8" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 09</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">Mobile app download badges sit alongside the link groups.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-navy text-slate-300">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid gap-10 lg:grid-cols-4">
                    <div>
                        <div class="text-lg font-bold text-white">ScriptGain</div>
                        <p class="mt-3 text-sm text-slate-400">Monitor and restore from anywhere.</p>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row lg:flex-col">
                            <a href="#" class="inline-flex items-center gap-3 rounded-lg border border-white/15 px-4 py-2.5 transition hover:border-white/40">
                                <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M16.365 1.43c0 1.14-.493 2.27-1.177 3.08-.744.9-1.99 1.57-2.987 1.57-.12 0-.23-.02-.3-.03-.01-.06-.04-.22-.04-.39 0-1.15.572-2.27 1.206-2.98.804-.94 2.142-1.64 3.248-1.68.03.13.05.28.05.43zm4.565 15.71c-.03.07-.463 1.58-1.518 3.12-.945 1.34-1.94 2.71-3.43 2.71-1.517 0-1.9-.88-3.63-.88-1.698 0-2.302.91-3.67.91-1.377 0-2.332-1.26-3.428-2.8-1.287-1.82-2.323-4.63-2.323-7.28 0-4.28 2.797-6.55 5.552-6.55 1.448 0 2.675.95 3.6.95.865 0 2.222-1.01 3.902-1.01.613 0 2.886.06 4.374 2.19-.13.09-2.383 1.37-2.383 4.19 0 3.26 2.854 4.42 2.955 4.45z"/></svg>
                                <span><span class="block text-xs text-slate-400">Download On The</span><span class="block text-sm font-semibold text-white">App Store</span></span>
                            </a>
                            <a href="#" class="inline-flex items-center gap-3 rounded-lg border border-white/15 px-4 py-2.5 transition hover:border-white/40">
                                <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M3.609 1.814 13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734c0-.377.223-.71.609-.92zm10.89 10.893 2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198 2.807 1.626a1.06 1.06 0 0 1 0 1.83l-2.808 1.626-2.499-2.499 2.5-2.583zM5.864 2.658 16.802 8.99l-2.302 2.302L5.864 2.658z"/></svg>
                                <span><span class="block text-xs text-slate-400">Get It On</span><span class="block text-sm font-semibold text-white">Google Play</span></span>
                            </a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Features</a></li>
                            <li><a href="#" class="transition hover:text-white">Pricing</a></li>
                            <li><a href="#" class="transition hover:text-white">Mobile App</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Resources</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Docs</a></li>
                            <li><a href="#" class="transition hover:text-white">Blog</a></li>
                            <li><a href="#" class="transition hover:text-white">Support</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">About</a></li>
                            <li><a href="#" class="transition hover:text-white">Careers</a></li>
                            <li><a href="#" class="transition hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 bg-black/30">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-white">Privacy</a><a href="#" class="transition hover:text-white">Terms</a><a href="#" class="transition hover:text-white">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 10 - Light, office addresses grid + region --}}
    {{-- =========================================================== --}}
    <section x-show="current === 9" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 10</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">Global office addresses with a region selector.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="flex flex-col gap-6 border-b border-slate-200 pb-10 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xl font-bold text-slate-900">ScriptGain</div>
                    <label class="sr-only" for="region10">Region</label>
                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2">
                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582" /></svg>
                        <select id="region10" class="bg-transparent text-sm text-slate-700 focus:outline-none"><option>Americas</option><option>Europe</option><option>Asia Pacific</option></select>
                    </div>
                </div>
                <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>San Francisco</h3>
                        <p class="mt-3 text-sm text-slate-600">548 Market Street<br>San Francisco, CA 94104</p>
                    </div>
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>London</h3>
                        <p class="mt-3 text-sm text-slate-600">1 Fore Street Avenue<br>London EC2Y 9DT</p>
                    </div>
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>Singapore</h3>
                        <p class="mt-3 text-sm text-slate-600">80 Raffles Place<br>Singapore 048624</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Quick Links</h3>
                        <ul class="mt-3 space-y-2.5 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Product</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200 bg-slate-50">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-brand-600">Privacy</a><a href="#" class="transition hover:text-brand-600">Terms</a><a href="#" class="transition hover:text-brand-600">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 11 - Oversized wordmark, dark, minimal --}}
    {{-- =========================================================== --}}
    <section x-show="current === 10" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 11</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A giant wordmark makes the brand the hero of the footer.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="overflow-hidden bg-navy text-slate-300">
            <div class="mx-auto max-w-7xl px-6 pt-16">
                <div class="flex flex-col gap-8 pb-12 lg:flex-row lg:justify-between">
                    <nav class="flex flex-wrap gap-x-8 gap-y-3 text-sm font-medium">
                        <a href="#" class="transition hover:text-white">Product</a>
                        <a href="#" class="transition hover:text-white">Solutions</a>
                        <a href="#" class="transition hover:text-white">Pricing</a>
                        <a href="#" class="transition hover:text-white">Docs</a>
                        <a href="#" class="transition hover:text-white">Company</a>
                    </nav>
                    <div class="flex gap-4">
                        <a href="#" aria-label="X" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                        <a href="#" aria-label="LinkedIn" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
                        <a href="#" aria-label="GitHub" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg></a>
                    </div>
                </div>
            </div>
            <div class="select-none px-6 text-center">
                <div class="text-[18vw] font-black leading-none tracking-tighter text-white/10 lg:text-[14vw]">ScriptGain</div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-white">Privacy</a><a href="#" class="transition hover:text-white">Terms</a><a href="#" class="transition hover:text-white">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 12 - Back-to-top bar + columns, light --}}
    {{-- =========================================================== --}}
    <section x-show="current === 11" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 12</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A back-to-top strip crowns the footer.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <a href="#" class="flex items-center justify-center gap-2 border-b border-slate-200 bg-slate-50 py-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                Back To Top
            </a>
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <div class="text-lg font-bold text-slate-900">ScriptGain</div>
                        <p class="mt-3 text-sm text-slate-500">Backups you never have to think about.</p>
                        <div class="mt-5 flex gap-3">
                            <a href="#" aria-label="X" class="text-slate-400 transition hover:text-brand-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                            <a href="#" aria-label="LinkedIn" class="text-slate-400 transition hover:text-brand-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Overview</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Pricing</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Security</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Resources</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Docs</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Blog</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Support</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">About</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200">
                <div class="mx-auto max-w-7xl px-6 py-6 text-sm text-slate-500"><p>&copy; 2026 ScriptGain. Privacy, Terms and Cookies.</p></div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 13 - Gradient dark, newsletter + columns --}}
    {{-- =========================================================== --}}
    <section x-show="current === 12" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 13</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A subtle brand gradient warms this dark footer.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-gradient-to-br from-brand-900 via-navy to-navy text-slate-300">
            <div class="mx-auto max-w-7xl px-6 py-16">
                <div class="grid gap-12 lg:grid-cols-3">
                    <div class="lg:col-span-1">
                        <div class="text-xl font-bold text-white">ScriptGain</div>
                        <p class="mt-3 max-w-sm text-sm text-slate-400">Get recovery tips and release notes in your inbox.</p>
                        <form class="mt-5 flex gap-3">
                            <label for="nl13" class="sr-only">Email Address</label>
                            <input id="nl13" type="email" placeholder="you@company.com" class="w-full rounded-lg border border-white/15 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <button type="submit" class="shrink-0 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-500">Sign Up</button>
                        </form>
                    </div>
                    <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-2">
                        <div>
                            <h3 class="text-sm font-semibold text-white">Product</h3>
                            <ul class="mt-4 space-y-3 text-sm">
                                <li><a href="#" class="transition hover:text-white">Overview</a></li>
                                <li><a href="#" class="transition hover:text-white">Pricing</a></li>
                                <li><a href="#" class="transition hover:text-white">Security</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Company</h3>
                            <ul class="mt-4 space-y-3 text-sm">
                                <li><a href="#" class="transition hover:text-white">About</a></li>
                                <li><a href="#" class="transition hover:text-white">Careers</a></li>
                                <li><a href="#" class="transition hover:text-white">Contact</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Resources</h3>
                            <ul class="mt-4 space-y-3 text-sm">
                                <li><a href="#" class="transition hover:text-white">Docs</a></li>
                                <li><a href="#" class="transition hover:text-white">Blog</a></li>
                                <li><a href="#" class="transition hover:text-white">Status</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-white">Privacy</a><a href="#" class="transition hover:text-white">Terms</a><a href="#" class="transition hover:text-white">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 14 - Dark two-tone, certifications + status --}}
    {{-- =========================================================== --}}
    <section x-show="current === 13" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 14</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">Certifications and a status pill on a dark two-tone footer.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-slate-900 text-slate-300">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid gap-10 lg:grid-cols-4">
                    <div>
                        <div class="text-lg font-bold text-white">ScriptGain</div>
                        <p class="mt-3 text-sm text-slate-400">Compliance built into every backup.</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Platform</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Agents</a></li>
                            <li><a href="#" class="transition hover:text-white">Repositories</a></li>
                            <li><a href="#" class="transition hover:text-white">Restore</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">About</a></li>
                            <li><a href="#" class="transition hover:text-white">Trust Center</a></li>
                            <li><a href="#" class="transition hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Certifications</h3>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">SOC 2 Type II</span>
                            <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">ISO 27001</span>
                            <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">GDPR</span>
                            <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">PCI DSS</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 bg-slate-950">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>All Systems Operational</span>
                        <p>&copy; 2026 ScriptGain.</p>
                    </div>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-white">Privacy</a><a href="#" class="transition hover:text-white">Terms</a><a href="#" class="transition hover:text-white">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 15 - Centered dark, social forward --}}
    {{-- =========================================================== --}}
    <section x-show="current === 14" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 15</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A centered dark footer that leads with community.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-navy text-slate-300">
            <div class="mx-auto max-w-3xl px-6 py-16 text-center">
                <div class="text-2xl font-bold text-white">ScriptGain</div>
                <p class="mx-auto mt-3 max-w-md text-sm text-slate-400">Join thousands of teams keeping their data safe and recoverable.</p>
                <div class="mt-8 flex justify-center gap-5">
                    <a href="#" aria-label="X" class="text-slate-400 transition hover:text-white"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                    <a href="#" aria-label="LinkedIn" class="text-slate-400 transition hover:text-white"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
                    <a href="#" aria-label="GitHub" class="text-slate-400 transition hover:text-white"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg></a>
                    <a href="#" aria-label="YouTube" class="text-slate-400 transition hover:text-white"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814z"/></svg></a>
                </div>
                <nav class="mt-8 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm font-medium text-slate-300">
                    <a href="#" class="transition hover:text-white">Product</a>
                    <a href="#" class="transition hover:text-white">Pricing</a>
                    <a href="#" class="transition hover:text-white">Docs</a>
                    <a href="#" class="transition hover:text-white">Company</a>
                </nav>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-3xl flex-col items-center gap-2 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-white">Privacy</a><a href="#" class="transition hover:text-white">Terms</a><a href="#" class="transition hover:text-white">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 16 - Light, contact-forward with phone/email --}}
    {{-- =========================================================== --}}
    <section x-show="current === 15" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 16</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A contact-forward footer with a call-to-action sidebar.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid gap-12 lg:grid-cols-3">
                    <div class="rounded-2xl bg-brand-50 p-8 ring-1 ring-brand-100">
                        <h2 class="text-lg font-bold text-slate-900">Talk To Our Team</h2>
                        <p class="mt-2 text-sm text-slate-600">We answer within one business day.</p>
                        <ul class="mt-5 space-y-3 text-sm">
                            <li><a href="tel:+18005550100" class="flex items-center gap-3 font-medium text-slate-700 transition hover:text-brand-600"><svg class="h-5 w-5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>+1 (800) 555 0100</a></li>
                            <li><a href="mailto:sales@example.com" class="flex items-center gap-3 font-medium text-slate-700 transition hover:text-brand-600"><svg class="h-5 w-5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>sales@scriptgain.com</a></li>
                        </ul>
                    </div>
                    <div class="grid grid-cols-2 gap-8 lg:col-span-2">
                        <div>
                            <div class="text-lg font-bold text-slate-900">ScriptGain</div>
                            <p class="mt-3 text-sm text-slate-500">Enterprise backup and recovery.</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Product</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li><a href="#" class="transition hover:text-brand-600">Overview</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Pricing</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Security</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Company</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li><a href="#" class="transition hover:text-brand-600">About</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Blog</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Legal</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li><a href="#" class="transition hover:text-brand-600">Privacy</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Terms</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Cookies</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200 bg-slate-50">
                <div class="mx-auto max-w-7xl px-6 py-6 text-sm text-slate-500"><p>&copy; 2026 ScriptGain. All Rights Reserved.</p></div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 17 - Newsletter card on brand-50 light --}}
    {{-- =========================================================== --}}
    <section x-show="current === 16" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 17</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A tinted footer with a raised newsletter card.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-brand-50">
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200 sm:p-10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-900">Stay In The Loop</h2>
                            <p class="mt-2 text-sm text-slate-500">Monthly digest of reliability engineering and product news.</p>
                        </div>
                        <form class="flex w-full max-w-md gap-3">
                            <label for="nl17" class="sr-only">Email Address</label>
                            <input id="nl17" type="email" placeholder="you@company.com" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                            <button type="submit" class="shrink-0 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Subscribe</button>
                        </form>
                    </div>
                </div>
                <div class="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <div class="text-lg font-bold text-slate-900">ScriptGain</div>
                        <p class="mt-3 text-sm text-slate-600">Reliable by default.</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Overview</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Pricing</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Docs</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">About</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Legal</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Privacy</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Terms</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Cookies</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-brand-100">
                <div class="mx-auto max-w-7xl px-6 py-6 text-sm text-slate-500"><p>&copy; 2026 ScriptGain. All Rights Reserved.</p></div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 18 - Split panel: dark logo + light links --}}
    {{-- =========================================================== --}}
    <section x-show="current === 17" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 18</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A split footer, dark brand panel beside light navigation.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer>
            <div class="grid lg:grid-cols-5">
                <div class="bg-navy px-6 py-14 text-slate-300 lg:col-span-2 lg:px-12">
                    <div class="text-2xl font-bold text-white">ScriptGain</div>
                    <p class="mt-4 max-w-xs text-sm text-slate-400">Set it once and never lose a byte. Automated backups with verified, instant recovery.</p>
                    <div class="mt-8 flex gap-4">
                        <a href="#" aria-label="X" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                        <a href="#" aria-label="LinkedIn" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
                        <a href="#" aria-label="GitHub" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg></a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-8 bg-white px-6 py-14 sm:grid-cols-3 lg:col-span-3 lg:px-12">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Overview</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Agents</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Pricing</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Resources</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">Docs</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Blog</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Status</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li><a href="#" class="transition hover:text-brand-600">About</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                            <li><a href="#" class="transition hover:text-brand-600">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200 bg-slate-50">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-brand-600">Privacy</a><a href="#" class="transition hover:text-brand-600">Terms</a><a href="#" class="transition hover:text-brand-600">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 19 - Mega footer, the works, dark --}}
    {{-- =========================================================== --}}
    <section x-show="current === 18" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 19</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">The full enterprise footer, CTA, columns, newsletter, badges.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="bg-navy text-slate-300">
            <div class="border-b border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col items-start gap-6 px-6 py-12 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-white">Start Protecting Your Data Today</h2>
                        <p class="mt-2 text-slate-400">No credit card required. Cancel anytime.</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="#" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-500">Get Started</a>
                        <a href="#" class="rounded-lg border border-white/20 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">Contact Sales</a>
                    </div>
                </div>
            </div>
            <div class="mx-auto max-w-7xl px-6 py-14">
                <div class="grid gap-10 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <div class="text-xl font-bold text-white">ScriptGain</div>
                        <p class="mt-3 max-w-xs text-sm text-slate-400">Automated, encrypted, verifiable backups for modern infrastructure.</p>
                        <form class="mt-6 flex max-w-sm gap-2">
                            <label for="nl19" class="sr-only">Email Address</label>
                            <input id="nl19" type="email" placeholder="Work Email" class="w-full rounded-lg border border-white/15 bg-white/5 px-3.5 py-2 text-sm text-white placeholder-slate-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <button type="submit" class="shrink-0 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-500">Join</button>
                        </form>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Overview</a></li>
                            <li><a href="#" class="transition hover:text-white">Agents</a></li>
                            <li><a href="#" class="transition hover:text-white">Pricing</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Solutions</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Enterprise</a></li>
                            <li><a href="#" class="transition hover:text-white">MSPs</a></li>
                            <li><a href="#" class="transition hover:text-white">Compliance</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Resources</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">Docs</a></li>
                            <li><a href="#" class="transition hover:text-white">Blog</a></li>
                            <li><a href="#" class="transition hover:text-white">Status</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Company</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="#" class="transition hover:text-white">About</a></li>
                            <li><a href="#" class="transition hover:text-white">Careers</a></li>
                            <li><a href="#" class="transition hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-12 flex flex-col gap-6 border-t border-white/10 pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap gap-3">
                        <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">SOC 2</span>
                        <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">ISO 27001</span>
                        <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">GDPR</span>
                        <span class="rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-slate-200">HIPAA</span>
                    </div>
                    <div class="flex gap-4">
                        <a href="#" aria-label="X" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                        <a href="#" aria-label="LinkedIn" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
                        <a href="#" aria-label="YouTube" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814z"/></svg></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 bg-black/30">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-white">Privacy</a><a href="#" class="transition hover:text-white">Terms</a><a href="#" class="transition hover:text-white">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

    {{-- =========================================================== --}}
    {{-- DESIGN 20 - Modern minimal light, manifesto + 3 columns --}}
    {{-- =========================================================== --}}
    <section x-show="current === 19" x-cloak class="flex min-h-screen flex-col">
        <div class="flex-1 bg-slate-50 px-6 py-16">
            <div class="mx-auto max-w-5xl">
                <span class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">Design 20</span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Preview Page Body</h1>
                <p class="mt-3 max-w-2xl text-slate-600">A modern, airy light footer with a short manifesto.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                    <div class="h-28 rounded-xl bg-white ring-1 ring-slate-200"></div>
                </div>
            </div>
        </div>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-6 py-16">
                <div class="grid gap-12 lg:grid-cols-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <svg class="h-7 w-7 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                            <span class="text-xl font-bold tracking-tight text-slate-900">ScriptGain</span>
                        </div>
                        <p class="mt-5 max-w-md text-lg leading-relaxed text-slate-600">We believe recovery should be boring. Set your policy, walk away, and trust that your data is always one click from restored.</p>
                    </div>
                    <div class="grid grid-cols-3 gap-8">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Product</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li><a href="#" class="transition hover:text-brand-600">Overview</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Pricing</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Docs</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Company</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li><a href="#" class="transition hover:text-brand-600">About</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Careers</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">Contact</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Connect</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li><a href="#" class="transition hover:text-brand-600">X</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">LinkedIn</a></li>
                                <li><a href="#" class="transition hover:text-brand-600">GitHub</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 ScriptGain. All Rights Reserved.</p>
                    <div class="flex gap-6"><a href="#" class="transition hover:text-brand-600">Privacy</a><a href="#" class="transition hover:text-brand-600">Terms</a><a href="#" class="transition hover:text-brand-600">Cookies</a></div>
                </div>
            </div>
        </footer>
    </section>

</div>
</body>
</html>
