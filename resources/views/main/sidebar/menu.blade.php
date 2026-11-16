<!--
    Sidebar items are rendered from the `menu` table (App\Models\UserManagement\Menu),
    filtered per user with Menu::isVisibleFor() (see App\View\Composers\SidebarComposer).
    Titles and section names are the fixed vocabulary of Database\Seeders\MenuSeeder and are
    translated with __() (keys in lang/{locale}.json).
-->
@include('main.sidebar.menulist')
