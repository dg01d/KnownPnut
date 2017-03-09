<div class="row">

    <div class="col-md-10 col-md-offset-1">
        <?=$this->draw('admin/menu')?>
        <h1>Pnut</h1>
    </div>

</div>
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <form action="/admin/pnut/" class="form-horizontal" method="post">
            <div class="controls-group">
                <div class="controls-config">
                    <p>
                        To begin using pnut.io, <a href="https://pnut.io/dev" target="_blank">create a new client in
                            the Pnut.io account portal</a>.</p>
                    <p>
                        The OAuth2 Redirect/callback URI should be set to:
                    </p>
                    <p>
                        <input type="text" name="ignore" class="form-control" value="<?=\Idno\Core\site()->config()->url?>pnut/callback" />
                    </p>
                </div>
            </div>
            <div class="controls-group">
                <p>
                    Once you've finished, fill in the details below:
                </p>
                <label class="control-label" for="name">Client ID</label>

                    <input type="text" id="name" placeholder="App Key" name="appId" class="form-control" value="<?=htmlspecialchars(\Idno\Core\site()->config()->pnut['appId'])?>" >

                <label class="control-label" for="name">Client Secret</label>

                    <input type="text" id="name" placeholder="Secret Key" name="secret" class="form-control" value="<?=htmlspecialchars(\Idno\Core\site()->config()->pnut['secret'])?>" >

            </div>

            <div class="controls-group">
                <p>
                    After the Pnut application is configured, site users must authenticate their Pnut account under <strong>Account Settings</strong>
                </p>
            </div>

            <div class="controls-group">
                <div class="controls-save">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
            <?= \Idno\Core\site()->actions()->signForm('/admin/pnut/')?>
        </form>
    </div>
</div>