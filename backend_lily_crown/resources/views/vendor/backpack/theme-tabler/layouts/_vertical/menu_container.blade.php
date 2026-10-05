{{--
    Lily Crown sidebar: logo on top, compact menu in the middle, signed-in user + theme switch + logout at the bottom.
    Overrides vendor/backpack/theme-tabler/resources/views/layouts/_vertical/menu_container.blade.php.
--}}
@if (backpack_auth()->check())
    <aside data-menu-theme="{{ $theme ?? 'system' }}" class="navbar navbar-vertical navbar-expand-lg navbar-{{ $theme ?? 'light' }} lc-sidebar @if(backpack_theme_config('options.sidebarFixed')) navbar-fixed @endif">
        <div class="container-fluid lc-sidebar-inner">
            {{-- Mobile top bar --}}
            <div class="d-flex d-lg-none align-items-center justify-content-between w-100">
                @include(backpack_view('layouts.partials.mobile_toggle_btn'), ['forceWhiteLabelText' => true])
                <a href="{{ url(backpack_theme_config('home_link')) }}" class="lc-brand-mobile text-decoration-none">{!! backpack_theme_config('project_logo') !!}</a>
                <span style="width: 2rem"></span>
            </div>

            <a class="navbar-brand d-none d-lg-flex lc-sidebar-brand" href="{{ url(backpack_theme_config('home_link')) }}" title="{{ backpack_theme_config('project_name') }}">
                {!! backpack_theme_config('project_logo') !!}
            </a>

            <div class="collapse navbar-collapse lc-sidebar-collapse" id="mobile-menu">
                <ul class="navbar-nav lc-menu">
                    @include(backpack_view('inc.sidebar_content'))
                </ul>

                <div class="lc-sidebar-user">
                    <a href="{{ config('backpack.base.setup_my_account_routes') ? route('backpack.account.info') : '#' }}" class="lc-sidebar-avatar text-decoration-none" title="My account (change password)">{{ mb_substr(backpack_user()->name ?? 'A', 0, 1, 'UTF-8') }}</a>
                    <span class="lc-sidebar-user-name">
                        <b>{{ backpack_user()->name }}</b>
                        <small>Administrator</small>
                    </span>
                    @if (backpack_theme_config('options.showColorModeSwitcher'))
                        <button type="button" onclick="colorMode.switch()" class="lc-sidebar-icon" title="Switch light / dark mode" aria-label="Switch light or dark mode">
                            <i class="la la-adjust"></i>
                        </button>
                    @endif
                    <a href="{{ backpack_url('logout') }}" class="lc-sidebar-icon" title="Logout" aria-label="Logout"><i class="la la-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </aside>
@endif
