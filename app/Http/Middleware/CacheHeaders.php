<?php

namespace App\Http\Middleware;

use Closure;

class CacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (! $request->isMethodCacheable() || ! $response->getContent() || auth()->id()) {
            return $response;
        }

        $options = [];
        $options['etag'] = md5($response->getContent());
        $options['public'] = true;

        switch ($request->route()->getName()) {
            // case 'api.product.get':
            //     $options['max_age'] = '300';
            //     break;

            // // Products cache
            // case 'api.products':
            //     $options['max_age'] = '1800';
            //     break;

            // Long cache
            case 'api.users':
            case 'api.user.get':
            case 'api.regions':
            case 'api.ratings':
            case 'api.rating.get':
            case 'api.rating_archives':
            case 'api.rating_archive.get':
            case 'api.banners':
            case 'api.banner.get':
            case 'api.brands':
            case 'api.brand.get':
            case 'api.campaigns':
            case 'api.campaign.get':
            case 'api.categories':
            case 'api.category.get':
            case 'api.subcategory.get':
            case 'api.colors':
            case 'api.color.get':
            case 'api.conditions':
            case 'api.condition.get':
            case 'api.groups':
            case 'api.group.get':
            case 'api.menus':
            case 'api.menu.get':
            case 'api.menu_items':
            case 'api.menu_item.get':
            case 'api.shipping_methods':
            case 'api.shipping_method.get':
            case 'api.sizes':
            case 'api.size.get':
            case 'api.sliders':
            case 'api.slider.get':
                $options['max_age'] = '3600';
        }

        $response->setCache($options);
        $response->isNotModified($request);

        return $response;
    }
}
