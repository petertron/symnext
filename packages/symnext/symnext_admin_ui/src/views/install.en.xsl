<?xml version="1.0" encoding="UTF-8"?>

<!-- English language text for install page -->

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:variable name="legend.preferences">Website Preferences</xsl:variable>
    <xsl:variable name="label.site_name">Site Name</xsl:variable>
    <xsl:variable name="label.admin_path">Admin Path</xsl:variable>
    <xsl:variable name="legend.date_time">Date and Time</xsl:variable>
    <xsl:variable name="label.region">Region</xsl:variable>
    <xsl:variable name="label.time_format">Time Format</xsl:variable>
    <xsl:variable name="legend.database">Database Connection</xsl:variable>
    <xsl:variable name="label.database">Database</xsl:variable>
    <xsl:variable name="label.database_user">User</xsl:variable>
    <xsl:variable name="label.database_password">Password</xsl:variable>
    <xsl:variable name="legend.advanced_config">Advanced Configuration</xsl:variable>
    <xsl:variable name="label.database_host">Host</xsl:variable>
    <xsl:variable name="label.database_port">Port</xsl:variable>
    <xsl:variable name="label.table_prefix">Table Prefix</xsl:variable>
    <xsl:variable name="legend.permissions">Permission Settings</xsl:variable>
    <xsl:variable name="label.file_perms">Files</xsl:variable>
    <xsl:variable name="label.dir_perms">Directories</xsl:variable>
    <xsl:variable name="legend.user_information">User Information</xsl:variable>
    <xsl:variable name="label.username">Username</xsl:variable>
    <xsl:variable name="label.password">Password</xsl:variable>
    <xsl:variable name="label.confirm_password">Confirm Password</xsl:variable>
    <xsl:variable name="legend.personal_information">Personal Information</xsl:variable>
    <xsl:variable name="label.first_name">First Name</xsl:variable>
    <xsl:variable name="label.last_name">Last Name</xsl:variable>
    <xsl:variable name="label.email">Email Address</xsl:variable>

    <xsl:template match="requirement[.='php-version']">
        <p>Symnext requires PHP version <xsl:value-of select="/data/params/php_version_min"/> or higher, however version <xsl:value-of select="/data/params/php_version"/> was detected.</p>
    </xsl:template>

    <xsl:template match="requirement[.='install-sql']">
        <p><code>install.sql</code> file is either missing or not readable. This is required to populate the database and must be uploaded before installation can commence.</p>
    </xsl:template>

    <xsl:template match="requirement[.='pdo']">
        <p>PHP PDO module is not present.</p>
    </xsl:template>

    <xsl:template match="requirement[.='zlib']">
        <p>PHP ZLib module is not present.</p>
    </xsl:template>

    <xsl:template match="requirement[.='xml']">
        <p>PHP XML module is not present.</p>
    </xsl:template>

    <xsl:template match="requirement[.='xslt']">
        <p>PHP XSLT module is not present.</p>
    </xsl:template>

    <xsl:template match="requirement[.='json']">
        <p>PHP JSON module is not present.</p>
    </xsl:template>

    <xsl:template match="requirement[.='root-directory']">
        <p>Symnext does not have write permission its root directory. Please modify permission settings on <code><xsl:value-of select="/data/params/root-directory"/></code>. This can be reverted once installation is complete.</p>
    </xsl:template>

    <xsl:template match="requirement[.='workspace']">
        <p>Symnext does not have write permission to the existing <code>workspace</code> directory. Please modify permission settings on this directory and its contents.</p>
    </xsl:template>

</xsl:stylesheet>
