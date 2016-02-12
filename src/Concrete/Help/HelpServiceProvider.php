<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 8/8/15
 * Time: 8:40 AM
 */

namespace Concrete\Package\ToessLabRssBlog\Help;
use Concrete\Core\Foundation\Service\Provider;

class HelpServiceProvider extends Provider {

    public function register()
    {
        $this->app['help/dashboard']->registerMessageString('/dashboard/rss_blog',
            t('All the pages generated from the RSS-Feed are listed here. By clicking "Get new Pages" all new RSS-entries are added.<br>Attention! When deleting Pages, those are deleted permanently.')
        );
        $this->app['help/dashboard']->registerMessageString('/dashboard/rss_blog/settings',
            t('Provide a valid RSS-Feed URL.<br>Select a parent Page where Blog Entry Pages will be added below.<br>Select Page Type for Blog Entries. Remember to edit the Page Type to your needs.<br> Select the Size of the desired Thumbnails defined in the<a href="%s">Thumbnail Settings</a>.<br>Add some HTML to be added below each Blog Entry Page (Optional)', \URL::to('/dashboard/system/files/thumbnails'))
        );
    }
}