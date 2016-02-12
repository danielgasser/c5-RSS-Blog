<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<?php
$form = Loader::helper('form');
$dh = Core::make('helper/date');
$session = \Core::make('session');
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
    <?php
    if($session->has('re_index')) {
        $m = $session->get('re_index');
        ?>
        <div class="alert alert-info">
            <a href="#" class="close">&times;</a>

            <div>
                <?php print $m ?>
            </div>
        </div>
    <?php
    }
    $session->remove('re_index');
    ?>
    <?php
    if($session->has('deleted')) {
        $m = $session->get('deleted');
        ?>
        <div class="alert alert-info">
            <a href="#" class="close">&times;</a>

            <div>
                <?php print $m ?>
            </div>
        </div>
    <?php
    }
    $session->remove('deleted');
    $del_pages = array();
    if(sizeof($pageData) > 0){
        foreach($pageData as $page) {
            $del_pages[] = $page['id'];
        }
    }
    $session->set('del_pages', $del_pages);
    ?>
    <div id="add_new" class="ccm-dashboard-header-buttons btn-group">
        <!--a href="<?php //print URL::to('/dashboard/rss_blog/create_pages') ?>" class="btn btn-primary"><?php //print t('Get new Pages') ?></a-->
        <a href="<?php print $view->action('delete_all_pages') ?>" class="btn btn-danger"><i class="fa fa-ban"></i> <?php print t('Delete all Pages!') ?></a>
    </div>
    <div class="row">
        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
            <strong><?php print t('Page is active/trashed') ?></strong>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
            <strong><?php print t('Page link') ?></strong>
        </div>
        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
            <strong><?php print t('Date added') ?></strong>
       </div>
        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
            <strong><?php print t('Delete page') ?></strong>
       </div>
    </div>
    <?php
    $i = 1;
    if(sizeof($pageData) > 0){
        foreach($pageData as $page){
            $date = new DateTime($page['date']);
            $print_date = $dh->formatDateTime($date->getTimestamp());
            ?>
            <div class="row">
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                    <?php print $i . '-'; print ($page['active'] == '1') ? t('Active') : '<span class=" fa fa-trash-o"></span>' ?>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <a href="<?php print $page['link'] ?>"><?php print $page['name'] ?></a>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                    <?php print $print_date ?>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                    <a id="delete_page" href="<?php print $view->action('delete_page', $page['id']) ?>" class="btn btn-danger btn-sm"><i class="fa fa-ban"></i> <?php print t('Delete!') ?></a>
                </div>
            </div>
        <?php
            $i++;
        }
    } ?>
</div>
