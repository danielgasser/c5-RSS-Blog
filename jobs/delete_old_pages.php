<?php
/**
 * Created by https://toesslab.ch/
 * User: Daenu
 * Date: 11/18/15
 * Time: 1:26 AM
 * Project: toesslab - Newsletter
 * Description: Send Newsletters to registered users
 * File: /packages/toess_lab_news_letter/jobs/send_newsletter_as_job.php
 */

namespace Concrete\Package\ToessLabRssBlog\Job;

use Concrete\Core\Area\Area;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\File\File;
use Concrete\Core\File\Importer;
use Concrete\Package\ToessLabRssBlog\Controller;
use Concrete\Package\ToessLabRssBlog\Controller\SinglePage\Dashboard\RssBlog;
use \Job as AbstractJob;
use \Concrete\Core\Tree\Type\Topic as TopicTree;
use \Concrete\Core\Tree\Node\Node as TreeNode;
use \Concrete\Core\Tree\Node\Type\Topic as TopicTreeNode;
use Loader;
use Concrete\Core\Page;

class DeleteOldPages extends AbstractJob
{

    public $jSupportsQueue = true;

    public function getJobName()
    {
        return t("toesslab - RSS Blog: Delete Old Pages");
    }

    public function getJobDescription()
    {
        return t("Delete old Blog pages provided by Toesslab RSS Blog");
    }

    public function run()
    {
        if (intval(\Config::get('toess_lab_rss_blog.settings.deleteAfter')) == 0) {
            //return t('Please adapt <a href="%s">Settings</a> first.', \URL::to('/dashboard/rss_blog/settings'));
        }
        $db = Loader::db();
        $days = \Config::get('toess_lab_rss_blog.settings.deleteAfter');
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $allPages = RssBlog::getPages();
        $pagesDeleted = 0;
        foreach($allPages as $page) {
            $pageDate = new \DateTime($page['date']);
            $pageDate->setTime(0, 0, 0);
            $pageDate->modify('+' . $days . 'day');
            if($pageDate <= $today){
                $p = Page\Page::getByID($page['id']);
                if(is_object($p)){
                    $p->delete();
                    $p = $db->execute('delete from ToessLabRssBlogPages where cID = ?', array($page['id']));
                    $pagesDeleted++;
                }
            }
        }
        return t('%s Page(s) deleted', $pagesDeleted);
    }
}
