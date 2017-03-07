<div class="row">

    <div class="col-md-10 col-md-offset-1">
        <h3>Pnut</h3>
        <?=$this->draw('account/menu')?>
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
                            <p>
                                If you have a Pnut.io account, you may connect it here. Public content that you
                                post to this site can then be cross-posted to your Pnut.io wall.
                            </p>
                            <p>
                                <a href="<?=$vars['login_url']?>" class="btn btn-large btn-success">Click here to connect Pnut.io to your account</a>
                            </p>
                        </div>
                    </div>
                <?php

                } else {

                    ?>
                    <div class="control-group">
                        <div class="controls">
                            <p>
                                Your account is currently connected to Pnut.io. Public content that you
                                post to this site can then be cross-posted to your Pnut.io wall.
                            </p>
                            <p>
                                <input type="hidden" name="remove" value="1" />
                                <button type="submit" class="btn btn-large btn-primary">Click here to remove Pnut.io from your account.</button>
                            </p>
                        </div>
                    </div>

                <?php

                }
            ?>
            <?= \Idno\Core\site()->actions()->signForm('/account/pnut/')?>
        </form>
    </div>
</div>