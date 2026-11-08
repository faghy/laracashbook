@php
    // Voci del menu, condivise tra versione desktop e mobile
    $links = [
        ['route' => 'movimenti.index', 'label' => __('Movements'), 'active' => 'movimenti.index'],
        ['route' => 'movimenti.oggi', 'label' => __('Today'), 'active' => null],
        ['route' => 'movimenti.ieri', 'label' => __('Yesterday'), 'active' => null],
        ['route' => 'categoria.index', 'label' => __('Categories'), 'active' => 'categoria.*', 'can' => 'admin'],
        ['route' => 'movimenti.cestino', 'label' => __('Trash'), 'active' => 'movimenti.cestino'],
    ];
    // Voci riservate (es. Categorie solo per admin e superadmin)
    $links = array_filter($links, fn ($link) => ! isset($link['can']) || Gate::allows($link['can']));
    $linkClass = 'px-3 py-2 rounded-lg font-medium transition-colors';
    $activeClass = 'bg-white/20 text-white';
    $idleClass = 'text-indigo-100 hover:bg-white/10 hover:text-white';
@endphp
<nav x-data="{ open: false }" class="bg-linear-to-r from-indigo-800 via-indigo-700 to-violet-700 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-4">
            <!-- Logo -->
            <a href="{{ route('movimenti.index') }}" class="flex items-center gap-2 shrink-0">
                <x-application-logo />
                <span class="text-lg font-bold text-white tracking-tight">{{ config('app.name') }}</span>
            </a>

            <!-- Navigation Links -->
            <div class="hidden lg:flex items-center gap-1">
                @foreach ($links as $link)
                    @php $isActive = $link['active'] && request()->routeIs($link['active']); @endphp
                    <a href="{{ route($link['route']) }}" @if ($isActive) aria-current="page" @endif
                       class="{{ $linkClass }} {{ $isActive ? $activeClass : $idleClass }}">{{ $link['label'] }}</a>
                @endforeach
            </div>

            <div class="hidden lg:flex items-center gap-3">
                <a href="{{ route('movimenti.create') }}" class="btn bg-white text-indigo-700 hover:bg-indigo-50 shadow-sm">{{ __('+ New movement') }}</a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-indigo-100 hover:bg-white/10 hover:text-white font-medium">
                            <span>{{ Auth::user()?->name }}</span>
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @can('admin')
                            <x-dropdown-link :href="route('dashboard')">
                                {{ __('Dashboard') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('utenti.index')">
                                {{ __('Users') }}
                            </x-dropdown-link>
                        @endcan
                        @can('superadmin')
                            <x-dropdown-link :href="route('entita.index')">
                                {{ __('Entities') }}
                            </x-dropdown-link>
                        @endcan

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center gap-2 lg:hidden">
                <a href="{{ route('movimenti.create') }}" class="btn btn-sm bg-white text-indigo-700">{{ __('+ New') }}</a>
                <button @click="open = ! open" aria-label="{{ __('Menu') }}" class="p-2 rounded-lg text-white hover:bg-white/10">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t border-white/15">
        <div class="px-4 py-3 flex flex-col gap-1">
            @foreach ($links as $link)
                @php $isActive = $link['active'] && request()->routeIs($link['active']); @endphp
                <a href="{{ route($link['route']) }}" @if ($isActive) aria-current="page" @endif
                   class="{{ $linkClass }} {{ $isActive ? $activeClass : $idleClass }}">{{ $link['label'] }}</a>
            @endforeach
        </div>

        <div class="px-4 py-3 border-t border-white/15">
            <div class="px-3 font-semibold text-white">{{ Auth::user()?->name }}</div>
            <div class="px-3 text-sm text-indigo-200">{{ Auth::user()?->email }}</div>

            <div class="mt-2 flex flex-col gap-1">
                @can('admin')
                    <a href="{{ route('dashboard') }}" class="{{ $linkClass }} {{ request()->routeIs('dashboard') ? $activeClass : $idleClass }}">{{ __('Dashboard') }}</a>
                    <a href="{{ route('utenti.index') }}" class="{{ $linkClass }} {{ request()->routeIs('utenti.*') ? $activeClass : $idleClass }}">{{ __('Users') }}</a>
                @endcan
                @can('superadmin')
                    <a href="{{ route('entita.index') }}" class="{{ $linkClass }} {{ request()->routeIs('entita.*') ? $activeClass : $idleClass }}">{{ __('Entities') }}</a>
                @endcan
                <a href="{{ route('profile.edit') }}" class="{{ $linkClass }} {{ request()->routeIs('profile.edit') ? $activeClass : $idleClass }}">{{ __('Profile') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left {{ $linkClass }} {{ $idleClass }}">{{ __('Log out') }}</button>
                </form>
            </div>
        </div>
    </div>
</nav>
