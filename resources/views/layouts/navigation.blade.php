<nav x-data="{ open: false }" class="bg-navy-800 border-b border-navy-900 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo / Nom -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ Auth::user()?->dashboardRoute() ? route(Auth::user()->dashboardRoute()) : route('login') }}"
                       class="flex items-center gap-2">
                        <img src="{{ asset('images/logo-hotel-president-icon.jpg') }}" alt="Hôtel Président"
                             class="h-9 w-9 rounded-full object-cover">
                        <span class="text-white font-semibold tracking-wide text-lg hidden sm:block">
                            Hôtel Président
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:ms-10 sm:flex sm:items-center">
                    @auth
                        @php
                            $role = Auth::user()->role;

                            $navLinkClasses = fn (bool $active) => $active
                                ? 'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-navy-700 text-white'
                                : 'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-navy-100 hover:bg-navy-700 hover:text-white transition';
                        @endphp

                        <a href="{{ route(Auth::user()->dashboardRoute()) }}"
                           class="{{ $navLinkClasses(request()->routeIs(Auth::user()->dashboardRoute())) }}">
                            Dashboard
                        </a>

                        <a href="{{ route('work-orders.index') }}"
                           class="{{ $navLinkClasses(request()->routeIs('work-orders.*')) }}">
                            Ordres de travail
                        </a>

                        <a href="{{ route('planning.index') }}"
                           class="{{ $navLinkClasses(request()->routeIs('planning.*')) }}">
                            Planning
                        </a>

                        @if (in_array($role, ['admin', 'manager']))
                            <a href="{{ route('suppliers.index') }}"
                               class="{{ $navLinkClasses(request()->routeIs('suppliers.*')) }}">
                                Fournisseurs
                            </a>

                            <a href="{{ route('purchase-orders.index') }}"
                               class="{{ $navLinkClasses(request()->routeIs('purchase-orders.*')) }}">
                                Achats
                            </a>

                            <a href="{{ route('parts.index') }}"
                               class="{{ $navLinkClasses(request()->routeIs('parts.*')) }}">
                                Stock
                            </a>

                            <a href="{{ route('maintenance-plans.index') }}"
                               class="{{ $navLinkClasses(request()->routeIs('maintenance-plans.*')) }}">
                                Maintenance préventive
                            </a>

                            <a href="{{ route('reports.index') }}"
                               class="{{ $navLinkClasses(request()->routeIs('reports.*')) }}">
                                Rapports
                            </a>
                        @endif

                        @if ($role === 'admin')
                            <x-dropdown align="left" width="56">
                                <x-slot name="trigger">
                                    <button type="button"
                                        class="{{ $navLinkClasses(request()->routeIs(['sla-policies.*', 'escalation-rules.*', 'work-order-types.*', 'work-order-priorities.*', 'skills.*', 'users.*', 'activity-logs.*'])) }} focus:outline-none">
                                        Administration
                                        <svg class="ms-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('users.index')">Utilisateurs</x-dropdown-link>
                                    <x-dropdown-link :href="route('skills.index')">Compétences</x-dropdown-link>
                                    <x-dropdown-link :href="route('work-order-types.index')">Types OT</x-dropdown-link>
                                    <x-dropdown-link :href="route('work-order-priorities.index')">Priorités</x-dropdown-link>
                                    <x-dropdown-link :href="route('sla-policies.index')">Politiques SLA</x-dropdown-link>
                                    <x-dropdown-link :href="route('escalation-rules.index')">Règles escalade</x-dropdown-link>
                                    <x-dropdown-link :href="route('activity-logs.index')">Journal d'activité</x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            @auth
                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md text-navy-100 hover:bg-navy-700 hover:text-white transition focus:outline-none">
                                <span class="flex items-center justify-center h-7 w-7 rounded-full bg-navy-600 text-white text-xs font-semibold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                Profil
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    Déconnexion
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            @endauth

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-navy-100 hover:text-white hover:bg-navy-700 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-navy-900">
        @auth
            @php $role = Auth::user()->role; @endphp

            <div class="pt-2 pb-3 space-y-1 px-2">
                <x-responsive-nav-link :href="route(Auth::user()->dashboardRoute())" :active="request()->routeIs(Auth::user()->dashboardRoute())">
                    Dashboard
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('work-orders.index')" :active="request()->routeIs('work-orders.*')">
                    Ordres de travail
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('planning.index')" :active="request()->routeIs('planning.*')">
                    Planning
                </x-responsive-nav-link>

                @if (in_array($role, ['admin', 'manager']))
                    <x-responsive-nav-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')">
                        Fournisseurs
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('purchase-orders.index')" :active="request()->routeIs('purchase-orders.*')">
                        Achats
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('parts.index')" :active="request()->routeIs('parts.*')">
                        Stock
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('maintenance-plans.index')" :active="request()->routeIs('maintenance-plans.*')">
                        Maintenance préventive
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                        Rapports
                    </x-responsive-nav-link>
                @endif

                @if ($role === 'admin')
                    <div class="px-3 pt-3 pb-1 text-xs font-semibold text-navy-400 uppercase tracking-wider">Administration</div>
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">Utilisateurs</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('skills.index')" :active="request()->routeIs('skills.*')">Compétences</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('work-order-types.index')" :active="request()->routeIs('work-order-types.*')">Types OT</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('work-order-priorities.index')" :active="request()->routeIs('work-order-priorities.*')">Priorités</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('sla-policies.index')" :active="request()->routeIs('sla-policies.*')">Politiques SLA</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('escalation-rules.index')" :active="request()->routeIs('escalation-rules.*')">Règles escalade</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('activity-logs.index')" :active="request()->routeIs('activity-logs.*')">Journal d'activité</x-responsive-nav-link>
                @endif
            </div>

            <div class="pt-4 pb-3 border-t border-navy-700">
                <div class="px-4 flex items-center gap-3">
                    <span class="flex items-center justify-center h-9 w-9 rounded-full bg-navy-600 text-white text-sm font-semibold">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <div>
                        <div class="font-medium text-white">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-navy-300">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1 px-2">
                    <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            Déconnexion
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>