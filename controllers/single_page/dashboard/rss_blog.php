<?php

namespace Concrete\Package\ToessLabRssBlog\Controller\SinglePage\Dashboard;
use Application\Core\User\UserInfo;
use Concrete\Controller\SinglePage\Dashboard\System\Attributes\Topics;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Config;
use Concrete\Core\File\File;
use Concrete\Core\File\Importer;
use Concrete\Core\File\Version;
use \Concrete\Core\Tree\Node\Type\Topic as TopicTreeNode;
use \Concrete\Core\Tree\Node\Node as TreeNode;
use Concrete\Package\ToessLabRssBlog\Controller;
use Loader;
use Concrete\Core\Page;
use Concrete\Core\Area;
use \Concrete\Core\Page\Controller\DashboardPageController;
use \Concrete\Core\Tree\Type\Topic as TopicTree;
use CollectionAttributeKey;
use PageEditResponse;

class RssBlog extends DashboardPageController
{

    public function view()
    {
        $data = $this->getPages();
        if (!$data) {
            $this->set('message', 'No Pages available."');
        }
        $this->set('pageData', $data);
    }

    public function on_start()
    {
        $this->requireAsset('toesslab');
        parent::on_start();
    }

    /**
     * @return array|bool
     */
    public static function getPages()
    {
        $data = array();
        $db = Loader::db();
        $p = $db->execute('select * from ToessLabRssBlogPages where cHandle like ? order by cHandle asc', array(Controller::$pagePrefix . '%'));
        if(sizeof($p) == 0) {
            return true;
        } else {
            $i = 0;
            foreach($p as $pid){
                $cp = Page\Page::getByID($pid['cID']);

                $data[$i]['id'] = $cp->cID;
                $data[$i]['link'] = $cp->getCollectionLink();
                $data[$i]['active'] = $cp->cIsActive;
                $data[$i]['name'] = $cp->getCollectionName();
                $data[$i]['date'] = $cp->getCollectionDatePublic();
                $i++;
            }
        }
        return $data;
    }

    /**
     * @param $arg
     * @return bool
     */
    public static function pagesExist($arg)
    {
        $db = Loader::db();
        $p = $db->execute('select cID, cHandle from ToessLabRssBlogPages where cHandle = ?', array($arg));
        if($p->rowCount() == 0) {
            return false;
        }
        while($row = $p->fetchRow()) {
            $cp = Page\Page::getByID($row['cID']);
            if($cp->cIsActive == '1'){
                return $row['cID'];
            }
        }
        return false;
    }

    /**
     * @param bool $pid
     * @param bool $json
     */
    public static function delete_page($pid = false, $json = true)
    {
        $session = \Core::make('session');
        $page = Page\Page::getByID($pid);
        if(is_object($page) && $page != NULL){
            $pt = $page->getCollectionName();
            $page->delete();
            $page = null;
        }
        if(!gc_enabled()){
            gc_enable();
        }
        gc_collect_cycles();

        if ($json){
            $session->set('deleted', t('The Page \'%s\' has been deleted.', $pt));
            $response = \Redirect::to('/dashboard/rss_blog');
            $response->send();
            exit;
        }
    }

    /**
     * @param array $pages
     */
    public static function delete_all_pages($pages = array())
    {
        $session = \Core::make('session');
        if (sizeof($pages) == 0){
            $pages = $session->get('del_pages');
        }

        if(sizeof($pages) > 0){
            foreach($pages as $p){
                self::delete_page($p, false);
                \Config::save('toess_lab_rss_blog.memory.delete_page', memory_get_usage());
            }
            $session->remove('del_pages');
        }
        if(!gc_enabled()){
            gc_enable();
        }
        gc_collect_cycles();

    }

}

