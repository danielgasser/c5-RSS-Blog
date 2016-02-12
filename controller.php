<?php

namespace Concrete\Package\ToessLabRssBlog;
use Concrete\Core\Attribute\Type as AttributeType;
use Concrete\Core\Asset;
use BlockType;
use Concrete\Core\File\File;
use Concrete\Core\File\Importer;
use Concrete\Core\Job\Job as Job;
use CollectionAttributeKey;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Tree\Type\Topic;
use Concrete\Core\View\View;
use Concrete\Package\ToessLabRssBlog\Controller\SinglePage\Dashboard\RssBlog;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Package\ToessLabRssBlog\Help\HelpServiceProvider;
use \Concrete\Core\Page\Search\IndexedSearch;
use Concrete\Core\Package\Package;
use \Concrete\Core\Tree\Type\Topic as TopicTree;


class Controller extends Package {

    /**
     * @var string
     */
    protected $pkgHandle = 'toess_lab_rss_blog';
    protected $appVersionRequired = '5.7.4.2';
    protected $pkgVersion = '0.9';
    protected $pkgAutoloaderMapCoreExtensions = true;
    public static $pagePrefix = 'toesslab_rss_blog_';

    public function getPackageDescription()
    {
        return t("Adds RSS feeds as blog entry.");
    }

    public function getPackageName()
    {
        return t("toesslab - RSS Blog");
    }


    public function install()
    {

        $dataTree =array(
            'akHandle' => 'toesslab_Rss_Blog',
            'akName' => t('Topics for Rss Blog'),
            'akIsSearchable' => 1,
            'akCheckedByDefault' => 1,
            'akIsSearchableIndexed' => 1,
            'akIsAutoCreated' => 1,
            'asID' => 2,
            'akCategoryID' => 3,
            'akTopicParentNodeID' => 55
        );
        $pkg = parent::install();

        $tpl = Template::getByHandle('full');
        if(!is_object($tpl)) {
            $tpl = Template::add('full', t('Full'));
            $tpl->update('full', t('Full'), 'full.png');
        }
        $dataType = array(
            'handle' => 'toesslab_blog_entry',
            'name' => 'toesslab  ' . t('Blog Entry'),
            'defaultTemplate' => $tpl,
            'allowedTemplates' => 'A',
            'ptIsFrequentlyAdded' => 1,
            'ptLaunchInComposer' => 1

        );
        $pageType = Type::add($dataType);
        self::installDefaultImage();
        $at = \Concrete\Core\Attribute\Type::getByHandle('image_file');
        $tb = CollectionAttributeKey::getByHandle('thumbnail');
        if(!is_object($tb)){
            $args = array(
                'akHandle' => 'thumbnail',
                'akName' => 'Thumbnail',
                'asID' => '0',
                'akIsSearchableIndexed' => 1,
                'akIsSearchable' => 1,
                'atID' => $at->getAttributeTypeID(),
                'akCategoryID' => '1');
            CollectionAttributeKey::add($at, $args);
        }

        $pageTypeList = Type::getList();
        $plList = array();
        foreach($pageTypeList as $pl){
            $plList[$pl->ptHandle] = $pl->ptName;
        }
        \Config::save('toess_lab_rss_blog.settings.template', 'toesslab_byline.php');
        \Config::save('toess_lab_rss_blog.settings.pageTemplate', 'toesslab_blog_entry');
        \Config::save('toess_lab_rss_blog.settings.pageTypeList', $plList);
        Job::installByPackage('get_new_pages', $pkg);
        Job::installByPackage('delete_old_pages', $pkg);
        $this->installOrUpgrade($pkg);
        $attributeType = AttributeType::getByHandle('topics');
        $attribute = CollectionAttributeKey::add($attributeType, $dataTree);
        \Config::save('toess_lab_rss_blog.settings.attribute.akID', $attribute->getAttributeKeyID());
        \Config::save('toess_lab_rss_blog.settings.attribute.akHandle', $attribute->getAttributeKeyHandle());
        \Config::save('toess_lab_rss_blog.settings.attribute.akName', $dataTree['akName']);
        $topicTree = TopicTree::add('toesslab RSS Blog Entries');
        \Config::save('toess_lab_rss_blog.settings.topicTree', $topicTree->treeID);
        $at = \Concrete\Core\Attribute\Type::getByHandle('image_file');
        $tb = CollectionAttributeKey::getByHandle('thumbnail');
        if(!is_object($tb)){
            $args = array(
                'akHandle' => 'thumbnail',
                'akName' => 'Thumbnail',
                'asID' => '0',
                'akIsSearchableIndexed' => 1,
                'akIsSearchable' => 1,
                'atID' => $at->getAttributeTypeID(),
                'akCategoryID' => '1');
            CollectionAttributeKey::add($at, $args);
        }
        RssBlog\Settings::getTopicTrees();
        RssBlog\Settings::activateTopicsCategory();
    }

    public function upgrade()
    {
        parent::upgrade();
        $pkg = self::getPackageHandle();
    }

    public function uninstall()
    {
        $attr_query = 'DELETE FROM AttributeKeys WHERE akHandle=?';
        $job = Job::getByHandle('get_new_pages');
        if(is_object($job)){
            $job->uninstall();
        }
        $job = Job::getByHandle('delete_old_pages');
        if(is_object($job)){
            $job->uninstall();
        }
        $stmt = Package::getByHandle(self::getPackageHandle())
            ->getEntityManager()
            ->getConnection();
        $configFile = DIR_CONFIG_SITE . '/generated_overrides/toess_lab_rss_blog.php';
        if (file_exists($configFile) && is_writable($configFile)) {
            unlink($configFile);
        }
        $del_pages = array();
        if(sizeof(RssBlog::getPages()) > 0){
            foreach(RssBlog::getPages() as $page) {
                $del_pages[] = $page['id'];
            }
            RssBlog::delete_all_pages($del_pages);
        }
        $pageType = Type::getByHandle('toesslab_blog_entry');
        if(is_object($pageType) && $pageType != NULL){
            $pageType->delete();
        }
        $topicTree = Topic::getByName('toesslab RSS Blog Entries');
        if(is_object($topicTree) && $topicTree != NULL){
            $topicTree->delete();
        }
        $stmt->executeQuery($attr_query, array('toesslab_Rss_Blog'));
        parent::uninstall();
    }

    public function on_start()
    {
        $app = \Core::make('app');
        $provider = new HelpServiceProvider($app);
        $provider->register();
        \Events::addListener('on_page_delete', array($this, 'indexPages'));
        \Events::addListener('on_page_add', array($this, 'indexPages'));
        \Events::addListener('on_page_version_approve', array($this, 'indexPages'));
        $pkg = $this;
        $al = Asset\AssetList::getInstance();
        $al->register(
            'css', 'toesslab', 'css/toesslab.css', array('position' => \Asset::ASSET_POSITION_HEADER), $pkg
        );
        $al->register(
            'javascript', 'toesslab', 'js/toesslab.js', array('position' => \Asset::ASSET_POSITION_FOOTER), $pkg
        );
        $al->registerGroup('toesslab', array(
            array('css', 'toesslab'),
            array('javascript', 'toesslab'),

        ));
        \Events::addListener('on_before_render', function() {
            $v = View::getInstance();
            $v->addFooterAsset('<link rel="stylesheet" type="text/css" href="' . BASE_URL . '/packages/' . self::getPackageHandle() . '/css/toesslab.css">');
        });
    }

    public function indexPages()
    {
        \Cache::disableAll();
        $session = \Core::make('session');
        $is = new IndexedSearch();
        $attributes = \CollectionAttributeKey::getList();
        $attributes = array_merge($attributes, \FileAttributeKey::getList());
        $attributes = array_merge($attributes, \UserAttributeKey::getList());
        foreach ($attributes as $ak) {
            $ak->updateSearchIndex();
        }

        $result = $is->reindexAll(true);

        if ($result->count == 0) {
            $session->set('re_index', t('Indexing complete. Index is up to date'));
        } else if ($result->count == $is->searchBatchSize) {
            $session->set('re_index', t(
                'Index partially updated. %s pages indexed (maximum number.) Re-run this job to continue this process.',
                $result->count
            ));
        } else {
            $session->set('re_index', t('Index updated.') . ' ' . t2(
                '%d page required reindexing.',
                '%d pages required reindexing.',
                $result->count,
                $result->count
            ));
        }
    }

    private function installDefaultImage()
    {
        $default_image = 'no_image.png';
        if (@copy('packages/' . self::getPackageHandle() . '/img/' . $default_image, 'application/files/incoming/' . self::$pagePrefix . $default_image)) {
            $f = new Importer();
            if($fv = $f->importIncomingFile(self::$pagePrefix . $default_image)){
                \Config::save('toess_lab_rss_blog.settings.defaultImage', $fv->getFileID());
            }
            @unlink(self::$pagePrefix . $default_image);
        }
        return \Config::get('toess_lab_rss_blog.settings.defaultImage');
    }

    private function installOrUpgrade($pkg)
    {

        $this->getOrAddSinglePage($pkg, '/dashboard/rss_blog', t('toesslab - RSS Blog'));
        $this->getOrAddSinglePage($pkg, '/dashboard/rss_blog/settings', t('RSS Blog Settings'));
    }

    private function getOrAddSinglePage($pkg, $cPath, $cName = '', $cDescription = '') {
        \Loader::model('single_page');

        $sp = SinglePage::add($cPath, $pkg);

        if (is_null($sp)) {
            $sp = Page::getByPath($cPath);
        } else {
            $data = array();
            if (!empty($cName)) {
                $data['cName'] = $cName;
            }
            if (!empty($cDescription)) {
                $data['cDescription'] = $cDescription;
            }

            if (!empty($data)) {
                $sp->update($data);
            }
        }

        return $sp;
    }

    public static function checkDefaultImage()
    {
        $file = File::getByID(\Config::get('toess_lab_rss_blog.settings.defaultImage'));
        if(!is_object($file)){
            return self::installDefaultImage();
        }
        return $file->getFileID();
    }
}