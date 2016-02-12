<?php

namespace Concrete\Package\ToessLabRssBlog\Controller\SinglePage\Dashboard\RssBlog;
use Concrete\Core\Config;
use Concrete\Core\File\Image\Thumbnail\Type\Type;

use Loader;
use Concrete\Core\Page;
use Concrete\Core\Area;
use \Concrete\Core\Page\Controller\DashboardPageController;
use \Concrete\Core\Tree\Type\Topic as TopicTree;

class Settings extends DashboardPageController
{

    public function view()
    {
        $this->set('feedUrl', \Config::get('toess_lab_rss_blog.settings.feedUrl'));
        $this->set('pageID', \Config::get('toess_lab_rss_blog.settings.parentPage'));
        if(sizeof(\Config::get('toess_lab_rss_blog.settings.topicTreeList')) == 0) {
            $this->error->add(t('No Topic Trees found. Please create one first.'));
        } else {
            $this->set('topicTreeList', \Config::get('toess_lab_rss_blog.settings.topicTreeList'));
        }
        $this->set('topicTree', \Config::get('toess_lab_rss_blog.settings.topicTree'));
        $this->set('htmlBlock', \Config::get('toess_lab_rss_blog.htmlBlock'));
        $this->set('pageTypeList', \Config::get('toess_lab_rss_blog.settings.pageTypeList'));
        $this->set('pageType', \Config::get('toess_lab_rss_blog.settings.pageType'));
        $this->set('thumbnailTypes', self::getThumbnailTypes());
        $this->set('thumbnailType', \Config::get('toess_lab_rss_blog.settings.thumbnailType'));
        $this->set('imgWidth', \Config::get('toess_lab_rss_blog.settings.imgWidth'));
        $this->set('imgHeight', \Config::get('toess_lab_rss_blog.settings.imgHeight'));
        $this->set('deleteAfter', \Config::get('toess_lab_rss_blog.settings.deleteAfter'));
        $this->set('defaultImage', \Config::get('toess_lab_rss_blog.settings.defaultImage'));
        $this->set('userAuthor', \Config::get('toess_lab_rss_blog.settings.userAuthor'));
        $this->set('showTopics', \Config::get('toess_lab_rss_blog.settings.showTopics'));
    }

    /**
     *
     * Saves settings to /application/config/generated_overrides/toess_lab_rss_blog.php
     */
    public function save_config()
    {
        if(strlen($this->post('feedUrl')) == 0) {
            $this->error->add(t('Provide an URL'));
        }
        $content = @file_get_contents($this->post('feedUrl'));
        if(!$content){
            $this->error->add(t('Invalid URL'));
        }
        if(intval($this->post('pageID')) == 0) {
            $this->error->add(t('Provide a Parent Page'));
        }
        if(intval($this->post('defaultImage')) == 0) {
            $this->error->add(t('Provide a default image'));
        }
        if(intval($this->post('deleteAfter')) == 0) {
            $this->error->add(t('Set the number of days after which pages will be deleted'));
        }
        if($this->error->has()) {
            $this->view();
            return;
        }
        if(strlen($this->post('htmlBlock')) > 0) {
            \Config::save('toess_lab_rss_blog.htmlBlock', $this->post('htmlBlock'));
        }
        $session = \Core::make('session');
        $imgWidth = intval($this->post('imgWidth'));
        $imgHeight = intval($this->post('imgHeight'));
        \Config::save('toess_lab_rss_blog.settings.feedUrl', $this->post('feedUrl'));
        \Config::save('toess_lab_rss_blog.settings.parentPage', intval($this->post('pageID')));
        \Config::save('toess_lab_rss_blog.settings.pageType', $this->post('pageType'));
        \Config::save('toess_lab_rss_blog.settings.thumbnailType', $this->post('thumbnailType'));
        \Config::save('toess_lab_rss_blog.settings.imgWidth', ($imgWidth <= 0) ? 696 : $imgWidth);
        \Config::save('toess_lab_rss_blog.settings.imgHeight', ($imgHeight <= 0) ? 464 : $imgHeight);
        \Config::save('toess_lab_rss_blog.settings.deleteAfter', intval($this->post('deleteAfter')));
        \Config::save('toess_lab_rss_blog.settings.defaultImage', intval($this->post('defaultImage')));
        \Config::save('toess_lab_rss_blog.settings.userAuthor', $this->post('userID'));
        \Config::save('toess_lab_rss_blog.settings.showTopics', $this->post('showTopics'));
        $this->activateTopicsCategory();
        $session->set('success', t('Settings have been saved'));
        $response = \Redirect::to('/dashboard/rss_blog/settings');
        $response->send();
        exit;
    }

    /**
     *
     * Gets Topic Trees and saves them to /application/config/generated_overrides/toess_lab_rss_blog.php
     */
    public static function getTopicTrees()
    {
        $topicTrees = array();
        if(sizeof(TopicTree::getList()) == 0) {
            \Config::save('toess_lab_rss_blog.settings.topicTreeList', array());
            return;
        }
        foreach(TopicTree::getList() as $val){
            $topicTrees[$val->treeID] = $val->topicTreeName;
        }
        \Config::save('toess_lab_rss_blog.settings.topicTreeList', $topicTrees);
    }

    /**
     * @return array of thumbnail types set in /dashboard/system/files/thumbnails
     */
    private function getThumbnailTypes()
    {
        $types = Type::getList();
        $typesArray = array();
        foreach($types as $tb_type){
            $typesArray[$tb_type->getHandle()] = $tb_type->getName();
        }
        return $typesArray;
    }

    /**
     * Activates the 1. (default) Category of the chosen TopicTree
     */
    public static function activateTopicsCategory()
    {
        self::getTopicTrees();
        $db = Loader::db();
        $akTopicParentNodeID = $db->getOne('select treeNodeID from TreeNodes where treeID = ?', array(\Config::get('toess_lab_rss_blog.settings.topicTree')));
        $args = array(
            'akID' => \Config::get('toess_lab_rss_blog.settings.attribute.akID'),
            'akHandle' => \Config::get('toess_lab_rss_blog.settings.attribute.akHandle'),
            'akName' => \Config::get('toess_lab_rss_blog.settings.attribute.akName'),
            'asID' => '0',
            'akIsSearchableIndexed' => '1',
            'akIsSearchable' => '1',
            'atID' => '10',
            'akCategoryID' => '1',
            'topicTreeIDSelect' => \Config::get('toess_lab_rss_blog.settings.topicTree'),
            'akTopicParentNodeID' => $akTopicParentNodeID,
            'akTopicTreeID' => \Config::get('toess_lab_rss_blog.settings.topicTree'),
        );
        $key = \CollectionAttributeKey::getByID(\Config::get('toess_lab_rss_blog.settings.attribute.akID'));
        $type = $key->getAttributeType();
        $cnt = $type->getController();
        $cnt->setAttributeKey($key);

        Type::getByID($args['atID']);
        $key->update($args);
    }

    public function on_start()
    {
        $this->getTopicTrees();
        parent::on_start();
    }

}