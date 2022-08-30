<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:form="symnext:form"
    extension-element-prefixes="form">

    <xsl:import href="install.en.xsl"/>
    <xsl:import href="form-field-templates.xsl"/>

    <xsl:output method="html"
        doctype-system="about:legacy-compat"
        omit-xml-declaration="yes"
        encoding="UTF-8"
        indent="yes" />

    <xsl:variable name="form" select="/data/form"/>

    <xsl:template match="/">
        <html lang="en">
            <head>
                <title>Install Symnext</title>
                <meta charset="UTF-8" />
                <meta name="robots" content="noindex" />
                <link rel="stylesheet" type="text/css" media="screen" href="{/*/params/domain}/installer.min.css" />
                <script defer="defer" src="/formul.js"></script>
                <style type="text/css">
div[data-error="required"]::after {
    content: "This is a required field";
}
                </style>
            </head>
            <body>
                <form name="form" action="" method="post">
                    <h1>Install Symnext <em>Version 0.1.0</em></h1>
                    <xsl:apply-templates select="/data/requirements"/>
                    <!--<xsl:apply-templates select="/data/form"/>-->
                    <xsl:call-template name="form"/>
                </form>
            </body>
        </html>
    </xsl:template>

    <xsl:template name="form">
        <fieldset>
            <legend><xsl:value-of select="$legend.preferences"/></legend>
            <xsl:copy-of select="form:input(
                $label.site_name,
                'fields[general][site_name]',
                $form/general/site_name)"/>
            <xsl:copy-of select="form:input(
                $label.site_name,
                'fields[admin][admin_path]',
                $form/admin/admin_path)"/>
            <fieldset class="frame">
                <legend><xsl:value-of select="$legend.date_time"/></legend>
                <p>Customise how Date and Time values are displayed throughout the Administration interface.</p>
                <label><xsl:value-of select="$label.region"/>
                    <select name="fields[region][timezone]">
                        <xsl:copy-of select="/*/timezones/*"/>
                    </select>
                </label>
                <label><xsl:value-of select="$label.time_format"/>
                    <select name="fields[region][time_format]">
                        <option value="H:i:s">14:08:56</option>
                        <option value="H:i">14:08</option>
                        <option value="g:i:s a">2:08:56 pm</option>
                        <option value="g:i a" selected="selected">2:08 pm</option>
                    </select>
                </label>
            </fieldset>
        </fieldset>
        <fieldset>
            <legend><xsl:value-of select="$legend.database"/></legend>
            <p>Please provide Symnext with access to a database.</p>
            <xsl:copy-of select="form:input(
                $label.database,
                'fields[database][database]',
                $form/database/database)"/>
            <div class="two columns">
                <xsl:copy-of select="form:input(
                    $label.database_user,
                    'fields[database][user]',
                    $form/database/user)"/>
                <xsl:copy-of select="form:input(
                    $label.database_password,
                    'fields[database][password]',
                    $form/database/password)"/>
            </div>
            <fieldset class="frame">
                <legend><xsl:value-of select="$legend.advanced_config"/></legend>
                <p>Leave these fields unless you are sure they need to be changed.</p>
                <div class="two columns">
                    <xsl:copy-of select="form:input(
                        $label.database_host,
                        'fields[database][host]',
                        $form/database/host)"/>
                    <xsl:copy-of select="form:input(
                        $label.database_port,
                        'fields[database][port]',
                        $form/database/port)"/>
                </div>
                <p>It is recommend to use <code>localhost</code> or <code>unix_socket</code> over <code>127.0.0.1</code> as the host on production servers. The port field can be used to specify the UNIX socket path.</p>
                <xsl:copy-of select="form:input(
                    $label.table_prefix,
                    'fields[database][table_prefix]',
                    $form/database/table_prefix)"/>
            </fieldset>
        </fieldset>
        <fieldset>
            <legend><xsl:value-of select="$legend.permissions"/></legend>
            <p>Set the permissions Symnext uses when saving files.</p>
            <div class="two columns">
                <xsl:copy-of select="form:input(
                    $label.file_perms,
                    'fields[files][file_write_mode]',
                    $form/files/file_write_mode)"/>
                <xsl:copy-of select="form:input(
                    $label.dir_perms,
                    'fields[files][directory_write_mode]',
                    $form/files/directory_write_mode)"/>
            </div>
        </fieldset>
        <fieldset>
            <legend><xsl:value-of select="$legend.user_information"/></legend>
            <p>Once installation is complete, you will be able to log in to the Symnext admin area with these user details.</p>
            <xsl:copy-of select="form:input(
                $label.username,
                'fields[user][username]',
                $form/files/user/username)"/>
            <div class="two columns">
                <xsl:copy-of select="form:input(
                    $label.password,
                    'fields[user][password]',
                    $form/files/user/password,
                    'password')"/>
                <xsl:copy-of select="form:input(
                    $label.confirm_password,
                    'fields[user][confirm_password]',
                    $form/files/user/confirm_password,
                    'password')"/>
            </div>
            <fieldset class="frame">
                <legend><xsl:value-of select="$legend.personal_information"/></legend>
                <p>Please add the following personal details for this user.</p>
                <div class="two columns">
                    <xsl:copy-of select="form:input(
                        $label.first_name,
                        'fields[user][first_name]',
                        $form/user/first_name)"/>
                    <xsl:copy-of select="form:input(
                        $label.last_name,
                        'fields[user][last_name]',
                        $form/user/last_name)"/>
                </div>
                <xsl:copy-of select="form:input(
                    $label.email,
                    'fields[user][email]',
                    $form/user/email)"/>
            </fieldset>
        </fieldset>
        <!--<h2>Install Symnext</h2>-->
        <div class="submit">
            <input name="lang" type="hidden" value="en" />
            <button name="action[install]" type="button">Install Symnext</button>
        </div>

        <template data-error="required">This is a required field</template>
        <template data-error="password-no-match">Does not match password</template>
    </xsl:template>

    <xsl:template match="requirements">
        <xsl:apply-templates select="requirement"/>
    </xsl:template>

    <xsl:template match="params/min-php-version">
        <code><abbr title="PHP: Hypertext Pre-processor">PHP</abbr><xsl:value-of select="."/></code>
    </xsl:template>

</xsl:stylesheet>
