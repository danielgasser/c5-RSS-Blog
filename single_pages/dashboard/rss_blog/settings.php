<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<?php
$form = Loader::helper('form');
$page = Core::make('helper/form/page_selector');
$session = \Core::make('session');
$f = new \Concrete\Core\Application\Service\FileManager();
$u = Core::make('helper/form/user_selector');
?>
<div class="clearfix">
    <?php
    if($session->has('success')) {
        $m = $session->get('success');
        ?>
        <div class="alert alert-info">
            <a href="#" class="close">&times;</a>

            <div>
                <?php print $m ?>
            </div>
        </div>
    <?php
    }
    $session->remove('success');
    ?>
    <form role="form" method="post" action="<?php print $controller->action('save_config')?>" class="form-inline ccm-search-fields">
        <fieldset>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                    <label class="control-label" for="feedUrl" name="feedUrl">
                        <?php print t('Feed Url') ?>
                    </label><br>
                    <?php print $form->text('feedUrl', $feedUrl, array('style' => 'width: 100%')); ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                    <label class="control-label" for="pageID" name="pageID">
                        <?php print t('Select Parent Page') ?>
                    </label><br>
                    <?php print $page->selectPage('pageID', $pageID); ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                    <label class="control-label" for="pageID" name="pageID">
                        <?php print t('Show topics on top of Blog Page') ?>
                    </label><br>
                    <?php print $form->select('showTopics', array('0' => t('No'), '1' => t('Yes'))); ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                    <label class="control-label" for="pageType" name="pageType">
                        <?php print t('Select Page Type for Blog entries') ?>
                    </label><br>
                    <?php print $form->select('pageType', $pageTypeList, (isset($pageType)) ? $pageType : 'toesslab_blog_entry'); ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                    <label class="control-label" for="thumbnailType" name="thumbnailType">
                        <?php print t('Select a Thumbnail Type for Images Blog entries') ?>
                    </label><br>
                    <?php print $form->select('thumbnailType', $thumbnailTypes, $thumbnailType) ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                    <label class="control-label" for="thumbnailType" name="thumbnailType">
                        <?php print t('Select a user as author') ?>
                    </label><br>
                    <?php print $u->selectUser('userID', (isset($userAuthor)) ? $userAuthor : '1') ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="form-group">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <label class="control-label" for="imgWidth" name="imgWidth">
                            <?php print t('Image size') ?>
                        </label><br>
                    </div>
                    <div class="col-sm-3">
                        <div class="input-group">
                            <span class="input-group-addon"><?php print t('Width') ?></span>
                            <?php
                            print $form->number('imgWidth', $imgWidth);
                            ?>
                            <span class="input-group-addon"><?php print t('Pixels') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="input-group">
                            <span class="input-group-addon"><?php print t('Height') ?></span>
                            <?php
                            print $form->number('imgHeight', $imgHeight);
                            ?>
                            <span class="input-group-addon"><?php print t('Pixels') ?></span>
                        </div>
                    </div>

                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="form-group">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <label class="control-label" for="imgWidth" name="imgWidth">
                            <?php print t('Default image (When RSS-Fedd does not provide one)') ?>
                        </label><br>
                    </div>
                    <div class="col-sm-12">
                        <div class="input-group">
                            <?php
                            $ml = (isset($defaultImage)) ? \Concrete\Core\File\File::getByID($defaultImage) : '';

                            print $f->image('defaultImage', 'defaultImage', '', $ml);
                            ?>
                        </div>
                    </div>

                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="form-group">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <label class="control-label" for="deleteAfter" name="deleteAfter">
                            <?php print t('Delete pages after') ?>
                        </label><br>
                    </div>
                    <div class="col-sm-3">
                        <div class="input-group">
                            <?php
                            print $form->number('deleteAfter', $deleteAfter);
                            ?>
                            <span class="input-group-addon"><?php print t('Days') ?></span>
                        </div>
                    </div>

                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="row">
                <div class="form-group">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <label class="control-label" for="htmlBlock" name="htmlBlock">
                            <?php print t('Add HTML (optional)') ?>
                        </label><br>
                    </div>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <?php
                            print $form->textarea('htmlBlock', $htmlBlock, array('rows' => '6', 'cols' => '50'));
                            ?>
                            <?php print t('Will be added below the Blog text') ?>
                        </div>
                    </div>

                </div>
            </div>
        </fieldset>
        <div class="ccm-dashboard-form-actions-wrapper">
            <div class="ccm-dashboard-form-actions">
                <button id="Send" name="Send" class="pull-right btn btn-primary" type="submit" ><?php echo t('Save settings')?></button>
            </div>
        </div>

    </form>
</div>
