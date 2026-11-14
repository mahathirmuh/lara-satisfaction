<!--
    Sidebar items are rendered from the `menu` table (App\Models\UserManagement\Menu),
    filtered per user with Menu::isVisibleFor() (see App\View\Composers\SidebarComposer).
-->
@include('main.sidebar.menulist')
