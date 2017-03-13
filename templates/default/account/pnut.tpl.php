<div class="row">

    <div class="col-md-10 col-md-offset-1">
        <?=$this->draw('account/menu')?>
        <h1>Pnut</h1>
    </div>

</div>
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <form action="/account/pnut/" class="form-horizontal" method="post">
            <?php
                if (empty(\Idno\Core\site()->session()->currentUser()->pnut)) {
            ?>
                    <div class="control-group">
                        <div class="controls">

                            <div class="row">
                                <div class="col-md-7">
                                    <p>
                                        If you have a Pnut.io account, you may connect it here. Public content that you
                                        post to this site can then be cross-posted to your Pnut.io wall.
                                    </p>
                                    <div class="social">
                                        <p>
                                            <a href="<?=$vars['login_url']?>" class="tw connect"><i class="fa fa-ra"></i>Connect Pnut.io</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php

                } else {

                    ?>
                    <div class="control-group">
                        <div class="controls">
                            <div class="row">
                                <div class="col-md-7">
                                    <p>
                                        Your account is currently connected to Pnut.io. Public content that you
                                        post to this site can then be cross-posted to your Pnut.io wall.
                                    </p>
                                    <p>
                                        <input type="hidden" name="remove" value="1" />
                                        <button type="submit" class="tw connect connected"><i class="fa fa-ra"></i>Disconnect Pnut.io</button>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php

                }
            ?>
            <?= \Idno\Core\site()->actions()->signForm('/account/pnut/')?>
        </form>
    </div>
</div>