<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>

<style>
    .container {
        width: 100%;
        margin: 0 auto;
        max-width: 980px;
        padding: 0 20px;
    }

    .content {
        text-align: center;
        margin-top: 60px;
        margin-bottom: 60px;
    }
</style>

<div class="container">
    <div class="content">
        <h2><?php p($l->t('Thank you!')); ?></h2>
        <h3><?php p($l->t('The signed file has been downloaded to your browser.')); ?></h3>
    </div>
</div>
