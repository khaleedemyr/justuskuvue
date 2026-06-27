<?php

namespace App\View\Composers;

use App\Services\YmsoftErpClient;
use App\Support\SiteMediaUrl;
use Illuminate\View\View;

class HomeLcpComposer
{
    public function __construct(private readonly YmsoftErpClient $erp)
    {
    }

    public function compose(View $view): void
    {
        if (! request()->routeIs('site.home')) {
            return;
        }

        $banners = $this->erp->get('web-profile/banners');
        $banner = is_array($banners[0] ?? null) ? $banners[0] : null;
        if ($banner === null) {
            return;
        }

        $image = (string) ($banner['image'] ?? '');
        $isVideo = ($banner['headIsVideo'] ?? false) === true
            || ($banner['headMediaType'] ?? '') === 'video'
            || preg_match('/\.(mp4|webm)(\?.*)?$/i', $image);

        if ($image === '' || $isVideo) {
            return;
        }

        $lcpUrl = SiteMediaUrl::resize($image, 768);
        $erpHost = parse_url($this->erp->webBaseUrl(), PHP_URL_HOST);

        $view->with('lcpPreloadUrl', $lcpUrl);
        $view->with('lcpHeroUrl', $lcpUrl);
        $view->with('lcpPreconnectHost', is_string($erpHost) && $erpHost !== '' ? 'https://'.$erpHost : null);
    }
}
