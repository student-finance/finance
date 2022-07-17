<?php
defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\Sample_Data;
?>
<div id="money-manager">
    <div class="html">
        <div class="body">
            <section class="hero is-fullheight is-info" style="margin-top:0!important;">
                <div class="hero-body">
                    <div class="container has-text-centered">
                        <p class="title is-1 is-spaced">
                            Welcome to Money Manager
                        </p>
                        <p class="subtitle is-3">
                            Thank you for installing the plugin!
                        </p>
                        <p class="subtitle is-3">
                            This software helps you organize your personal or business finances. You can always track where, when and how the money goes, thanks to the following features:
                        </p>

                        <div class="tile is-ancestor">
                            <div class="tile is-4 is-vertical is-parent">
                                <div class="tile is-child box notification is-info">
                                    <p class="subtitle">
                                        <span class="icon-text">
                                            <span class="icon">
                                                <i class="fas fa-coins"></i>
                                            </span>
                                            <span>Multi-Currency</span>
                                        </span>
                                    </p>
                                </div>
                                <div class="tile is-child box notification is-info">
                                    <p class="subtitle">
                                        <span class="icon-text">
                                            <span class="icon">
                                                <i class="fas fa-check-double"></i>
                                            </span>
                                            <span>Double-Entry System</span>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="tile is-4 is-vertical is-parent">
                                <div class="tile is-child box notification is-info">
                                    <p class="subtitle">
                                        <span class="icon-text">
                                            <span class="icon">
                                                <i class="fas fa-university"></i>
                                            </span>
                                            <span>Bank Accounts</span>
                                        </span>
                                    </p>
                                </div>
                                <div class="tile is-child box notification is-info">
                                    <p class="subtitle">
                                        <span class="icon-text">
                                            <span class="icon">
                                                <i class="fas fa-chart-line"></i>
                                            </span>
                                            <span>Income/Expense Tracking</span>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="tile is-4 is-vertical is-parent">
                                <div class="tile is-child box notification is-info">
                                    <p class="subtitle">
                                        <span class="icon-text">
                                            <span class="icon">
                                                <i class="fas fa-chart-pie"></i>
                                            </span>
                                            <span>Account Summaries</span>
                                        </span>
                                    </p>
                                </div>
                                <div class="tile is-child box notification is-info">
                                    <p class="subtitle">
                                        <span class="icon-text">
                                            <span class="icon">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                            <span>Transaction Categories</span>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <section class="section">
                            <?php if ( Sample_Data::imported() ): ?>
                                <button disabled class="button is-primary is-large">
                                <span class="icon is-small">
                                    <i class="fas fa-file-import"></i>
                                </span>
                                    <span>Import Sample Data & Start</span>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo esc_url( admin_url('admin.php?page=money-manager-home&money-manager-import=sample-data') ) ?>" class="button is-primary is-large">
                                <span class="icon is-small">
                                    <i class="fas fa-file-import"></i>
                                </span>
                                    <span>Import Sample Data & Start</span>
                                </a>
                            <?php endif ?>
                            <a href="<?php echo esc_url( admin_url('admin.php?page=money-manager-home') ) ?>" class="button is-large">
                                <span class="icon is-small">
                                    <i class="fas fa-play-circle"></i>
                                </span>
                                <span>Just Start</span>
                            </a>
                        </section>

                        <p class="subtitle is-3">
                            <?php if ( Sample_Data::imported() ): ?>
                                You have already imported the sample data. We hope you enjoy using the Money Manager.
                            <?php else: ?>
                                It is recommended to import the sample data, so you can just play around with some records, and then delete them.
                            <?php endif ?>
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
    #wpcontent {
        padding-left: 0;
    }
    #wpbody-content {
        padding-bottom: 0;
    }
    #wpbody-content > .notice, #wpfooter {
        display: none;
    }
</style>
