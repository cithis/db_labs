<?php

use FastRoute\RouteCollector;

function createCrudRoutes(RouteCollector $r, string $controller): void
{
    $r->addRoute('GET', '/list', "$controller:enumerate");
    $r->addRoute('GET', '/@{uuid}', "$controller:view");
    $r->addRoute('POST', '/@{uuid}', "$controller:edit"); # should also handle removal & creation
    $r->addRoute('GET', '/new', "$controller:create");    # only form for edit thingy
}

return (function (RouteCollector $r): void {
    $r->addRoute('GET', '/', 'Home:home');
    $r->addRoute('GET', '/ava/{amogus}.jpeg', 'Home:identicon');
    
    $r->addGroup('/users',    fn ($r) => createCrudRoutes($r, 'Users'));
    $r->addGroup('/creators', fn ($r) => createCrudRoutes($r, 'Creators'));
    $r->addGroup('/posts',    fn ($r) => createCrudRoutes($r, 'Posts'));
    $r->addGroup('/tiers',    fn ($r) => createCrudRoutes($r, 'Tiers'));

    $r->addRoute('GET', '/posts/feed', 'Posts:feed');
    $r->addRoute('GET', '/posts/feed/by-creator', 'Posts:creatorFeed');
    $r->addRoute('POST', '/posts/@{post}/bind', 'Posts:bind');
    $r->addRoute('POST', '/posts/@{post}/unbind/{tier}', 'Posts:unbind');
    
    $r->addRoute('POST', '/users/@{user}/sub', 'Users:sub');
    $r->addRoute('POST', '/subs/@{tx}', 'Users:editSub');

    $r->addGroup('/olap', function (RouteCollector $r): void {
        $r->addRoute('GET', '', 'Analytics:index');
        $r->addRoute(['GET', 'POST'], '/octopus', 'Analytics:octopusPosts');
        $r->addRoute(['GET', 'POST'], '/richest/users', 'Analytics:richestUsers');
        $r->addRoute(['GET', 'POST'], '/richest/creators', 'Analytics:richestCreators');
    });
});