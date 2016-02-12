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
use Concrete\Core\User\User;
use Concrete\Package\ToessLabRssBlog\Controller;
use Concrete\Package\ToessLabRssBlog\Controller\SinglePage\Dashboard\RssBlog;
use \Job as AbstractJob;
use \Concrete\Core\Tree\Type\Topic as TopicTree;
use \Concrete\Core\Tree\Node\Node as TreeNode;
use \Concrete\Core\Tree\Node\Type\Topic as TopicTreeNode;
use Loader;
use Concrete\Core\Page;

class GetNewPages extends AbstractJob
{

    public $jSupportsQueue = true;

    public function getJobName()
    {
        return t("toesslab - RSS Blog: Get new Pages");
    }

    public function getJobDescription()
    {
        return t("Refreshes Feeds provided by Toesslab RSS Blog");
    }

    public function run()
    {
        if(\Config::get('toess_lab_rss_blog.settings.feedUrl') == NULL){
            return t('Please adapt <a href="%s">Settings</a> first.', \URL::to('/dashboard/rss_blog/settings'));
        }

        $page_prefix = Controller::$pagePrefix;
        $topicTree = TopicTree::getByID(\Config::get('toess_lab_rss_blog.settings.topicTree'));
        $parent = TreeNode::getByID($topicTree->getRootTreeNodeObject()->treeNodeID);
        $content = @file_get_contents(\Config::get('toess_lab_rss_blog.settings.feedUrl'));
        if(!$content){
            return 0;
        }
        $xml = simplexml_load_file(\Config::get('toess_lab_rss_blog.settings.feedUrl'));
        $db = Loader::db();
        $blog = \Page::getByID(\Config::get('toess_lab_rss_blog.settings.parentPage'));
        $type = \PageType::getByHandle(\Config::get('toess_lab_rss_blog.settings.pageType'));
        $pageTemplate = \PageTemplate::getByHandle(\Config::get('toess_lab_rss_blog.settings.pageTemplate'));
        $template = \Config::get('toess_lab_rss_blog.settings.template');
        $akHandle = \Config::get('toess_lab_rss_blog.settings.attribute.akHandle');
        $htmlBlockContent = \Config::get('toess_lab_rss_blog.htmlBlock');
        $defaultImage = Controller::checkDefaultImage();
        $xmlArray = array();
        foreach($xml->children() as $val) {
            $counter = 0;
            foreach ($val->children()->item as $i) {
                $xmlArray[$counter]['published'] = (isset($i->pubDate)) ? new \DateTime($i->pubDate) : new \DateTime();
                $xmlArray[$counter]['title'] = (isset($i->title)) ? (string)$i->title : '';
                $xmlArray[$counter]['short_description'] = (isset($i->description)) ? explode('<p>', $i->description)[0] : '';
                $xmlArray[$counter]['description'] = (isset($i->description)) ? (string)$i->description : '';
                $xmlArray[$counter]['link'] = (isset($i->link)) ? (string)$i->link : '';
                $xmlArray[$counter]['categories'] = (isset($i->category)) ? (array)$i->category : array();
                if (isset($i->enclosure)) {
                    foreach ((array)$i->enclosure as $e) {
                        foreach ($e as $k => $a) {
                            $xmlArray[$counter]['enclosure'][$k] = $a;
                        }
                    }
                }
                $xmlArray[$counter]['author'] = (isset($i->author)) ? (string)$i->author : '';
                $counter++;
            }
        }
        $pageAdded = 0;
        foreach($xmlArray as $value) {
            $title = str_replace(' ', '_', $value['title']);
            $collection_handle = $page_prefix . preg_replace('/[^A-Za-z0-9\_]/', '', $title);
            if(!RssBlog::pagesExist($collection_handle)) {

                // Create page
                $entry = $blog->add($type, array(
                    'cName' => $value['title'],
                    'cDescription' => $value['short_description'],
                    'cHandle' => $collection_handle,
                    'cvIsApproved' => true,
                    'cDatePublic' => $value['published']->format('Y-m-d H:i:s')
                ), $pageTemplate);

                // Create categories/topics
                $itemsToActivate = array();
                $cat_block = '<div class="toesslab-rss-blog-categories-block">';
                foreach($value['categories'] as $key => $cat){
                    $r = $db->GetRow('select * from TreeTopicNodes where treeNodeTopicName = ?', array($cat));
                    if(sizeof($r) == 0){
                        $item_topic = TopicTreeNode::add($cat, $parent);
                    } else {
                        $item_topic = TopicTreeNode::getNodeByName($r['treeNodeTopicName']);
                    }
                    $itemsToActivate[] = $item_topic->getTreeNodeDisplayPath();
                    $cat_block .= '<p class="toesslab-rss-blog-category-' . $key . '">' . $cat . '</p>';
                }
                $cat_block .= '</div>';
                $entry->setAttribute($akHandle, $itemsToActivate);

                // Get images
                if(sizeof($value['enclosure']) > 0){
                    $file = $value['enclosure']['url'];
                    $newFileName = basename($file);
                    $img = self::getImages($file, $newFileName, $defaultImage);
                    $entry->setAttribute('thumbnail', array('fID' => $img->getFileID()));
                    $il = \Concrete\Core\File\File::getRelativePathFromID($img->getFileID());
                    $img_link = '<img title="' . $img->getTitle() . '" src="' . $il . '">';
                } else {
                    $img = File::getByID(\Config::get('toess_lab_rss_blog.settings.defaultImage'));
                    $entry->setAttribute('thumbnail', array('fID' => $img->getFileID()));
                    $il = \Concrete\Core\File\File::getRelativePathFromID($img->getFileID());
                    $img_link = '<img title="' . $img->getTitle() . '" src="' . $il . '">';
                }

                // Create content
                $author = self::getAuthorsName($value['description']);
                $title_content = self::createContent($value['title'], $value['published'], $author, $cat_block, $entry->getCollectionLink());

                // Create blocks
                $bt_content = BlockType::getByHandle('content');
                $bt_html = BlockType::getByHandle('html');
                $ax = Area::getOrCreate($entry, 'Main');
                $title_block = $entry->addBlock($bt_content, $ax, array('content' => $title_content));
                $block_content = $entry->addBlock($bt_content, $ax, array('content' => '<p class="toesslab-rss-blog-image">' . $img_link . '</p><div class="toesslab-rss-blog-description">' . $value['description'] . '</div>'));
                if(strlen($htmlBlockContent) > 0){
                    $html_block = $entry->addBlock($bt_html, $ax, array('content' => $htmlBlockContent));
                    $html_block->setCustomTemplate($template);
                }
                $title_block->setCustomTemplate($template);
                $block_content->setCustomTemplate($template);
                $pageAdded++;
            }
        }
        return t('%s new Page(s) added', $pageAdded);
    }

    private function getImages($link, $filename, $defaultImage)
    {
        $db = Loader::db();
        $p = $db->getRow('select * from FileVersions where fvFilename = ?', array($filename));
        $returnFile = File::getByID($defaultImage);
        if(!$p){
            $newFile = 'application/files/incoming/' . $filename;
            $img = @file_get_contents($link);
            if(strlen($img) > 0){
                $im = imagecreatefromstring($img);
                $width = imagesx($im);
                $height = imagesy($im);
                $newwidth = \Config::get('toess_lab_rss_blog.settings.imgWidth');
                $newheight = \Config::get('toess_lab_rss_blog.settings.imgHeight');
                $thumb = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresized($thumb, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
                imagejpeg($thumb, $newFile); //save image as jpg
                imagedestroy($thumb);
                imagedestroy($im);
                \Config::save('toess_lab_rss_blog.memory.xml_get_images_copy', memory_get_usage());
                $f = new Importer();
                if($fv = $f->importIncomingFile($filename)){
                    \Config::save('toess_lab_rss_blog.memory.xml_get_images', memory_get_usage());
                    $returnFile = File::getByID($fv->getFileID());
                }
            }
        } else {
            $returnFile = File::getByID($p['fID']);
        }
        @unlink($newFile);
        return $returnFile;
    }

    private function createContent($title, $published, $author, $cat_block, $pageLink)
    {
        $uid = (intval(\Config::get('toess_lab_rss_blog.settings.userAuthor')) > 0) ? \Config::get('toess_lab_rss_blog.settings.userAuthor') : '1';
        $user = User::getByUserID($uid);
        $username = $user->getUserName();
            if (\Config::get("concrete.user.profiles_enabled")) {
                $profileLink= '<a href="' . \URL::to('/profile/view/', $uid) . '">' . $username . '</a>';
            }else{
                $profileLink = $username;
            }
        $dh = \Core::make('helper/date');
        $content = '<div class="toesslab-ccm-block-page-title-byline">';
        if (\Config::get('toess_lab_rss_blog.settings.showTopics') == '1') {
            $content .= $cat_block;
        }
        $content .= '<div class="toesslab-page-title-container">';
        $content .= '<h1 class="toesslab-page-title">' . $title . '</h1>';
        $content .= '<p class="toesslab-page-profile-link">';
        $content .= t('Af').' <span class=""><i class="fa fa-user"></i> <b>' . $profileLink . '</b></span> ';
        $content .= t('kl').' <span class=""><i class="fa fa-clock-o"></i> ' . $dh->formatTime($published->getTimeStamp()) . '</span> ';
        $content .= t('den').' <span class=""><i class="fa fa-calendar"></i> ' . $published->format('d. M. Y') . '</span>';
        $content .= '</p>';
        $content .= '<div>';
        $content .= '<p>' . t('Del:');
        $content .= '<a href="mailto:?subject=' . $title . '&body=' . $pageLink . '">';
        $content .= '<i class="fa fa-envelope fa-border fa-lg"></i>';
        $content .= '</a>';
        $content .= '<a href="https://twitter.com/share">';
        $content .= '<i class="fa fa-twitter fa-border fa-lg"></i>';
        $content .= '</a>';
        $content .= '<a href="http://www.facebook.com/share.php?u=' . $pageLink . '">';
        $content .= '<i class="fa fa-facebook fa-border fa-lg"></i>';
        $content .= '</a>';
        $content .= '</p>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '<p class="toesslab-page-author">';
        // ToDo
        //$content .= $author;
        $content .= '</p>';
        $content .= '</div>';
        return $content;
    }

    private function getAuthorsName($description)
    {
        $d = explode('<p>', $description);
        $a = array_pop($d);
        $b = str_replace('</p>', '', $a);
        $c = str_replace('/', ' ', $b);
        return ucfirst(ltrim($c));
    }
}
