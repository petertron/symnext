<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>

	<xsl:variable name="page-title" select="'Settings'"/>
	<xsl:variable name="body-id" select="'system-settings'"/>
    <xsl:variable name="form" select="document(/*/params/config_file)/configuration"/>

    <!-- Strings for the page-->
    <xsl:param name="legend.essentials">Essentials</xsl:param>
    <xsl:param name="label.name">Name</xsl:param>
	<xsl:param name="label.handle">Handle</xsl:param>
	<xsl:param name="legend.options">Options</xsl:param>
	<xsl:param name="legend.fields">Fields</xsl:param>
    <xsl:param name="label.placement">Placement</xsl:param>
    <xsl:param name="label.make-required">Make this a required entry</xsl:param>
    <xsl:param name="label.display-entries">Display in entries table</xsl:param>

	<xsl:template name="context-actions">
	</xsl:template>

    <xsl:template match="/data" mode="contents">
		<fieldset class="settings">
			<legend><xsl:value-of select="'Site'"/></legend>
			<div class="two columns">
				<xsl:call-template name="form.select">
					<xsl:with-param name="label" select="'Site Mode'"/>
					<xsl:with-param name="key" select="'fields[site][mode]'"/>
					<xsl:with-param name="value" select="$form/site/mode"/>
                    <xsl:with-param name="options" select="site_mode_options/*"/>
				</xsl:call-template>
				<xsl:call-template name="form.input">
					<xsl:with-param name="label" select="'Site Name'"/>
					<xsl:with-param name="key" select="'fields[site][name]'"/>
					<xsl:with-param name="value" select="$form/site/name"/>
				</xsl:call-template>
			</div>
		</fieldset>
		<fieldset class="settings">
            <legend><xsl:value-of select="'Bonk'"/></legend>
		</fieldset>

		<xsl:apply-templates select="/data/fields_available/field" mode="section-field"><xsl:with-param name="status" select="'template'"/></xsl:apply-templates>

    </xsl:template>

    <xsl:template match="field" mode="section-field">
        <xsl:param name="field-type" select="@type"/>
        <xsl:param name="status" select="'instance'"/>
        <xsl:variable name="index">
            <xsl:choose>
                <xsl:when test="$status = 'template'">-1</xsl:when>
                <xsl:otherwise><xsl:value-of select="position() - 1"/></xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <li class="{$status} field-{@type}" data-type="{$field-type}">
            <xsl:variable name="field-type-name"><xsl:apply-templates select="." mode="section-field-name"/></xsl:variable>
            <header class="frame-header {location}" data-name="{$field-type-name}">
                <h4><strong>New Field</strong> <span class="type"><xsl:value-of select="$field-type-name"/></span></h4>
            </header>
            <input name="fields[{$index}][type]" type="hidden" value="{$field-type}"/>
            <div class="content">
                <div>
                    <xsl:call-template name="form.input">
                        <xsl:with-param name="label" select="$label.name"/>
                        <xsl:with-param name="key" select="concat('fields[', $index, '][name]')"/>
                        <xsl:with-param name="value" select="name"/>
                    </xsl:call-template>
                    <div class="two columns">
                        <xsl:call-template name="form.input">
                            <xsl:with-param name="label" select="$label.handle"/>
                            <xsl:with-param name="key" select="concat('fields[', $index, '][handle]')"/>
                            <xsl:with-param name="value" select="handle"/>
                        </xsl:call-template>
                        <xsl:call-template name="form.select">
                            <xsl:with-param name="label" select="$label.placement"/>
                            <xsl:with-param name="key" select="concat('fields[', $index, '][location]')"/>
                        </xsl:call-template>
                    </div>
                </div>
                <xsl:apply-templates select="." mode="section-field-contents">
                    <xsl:with-param name="index" select="$index"/>
                </xsl:apply-templates>
                <fieldset>
                    <div class="two columns">
                        <xsl:call-template name="form.checkbox">
                            <xsl:with-param name="label" select="$label.make-required"/>
                            <xsl:with-param name="key" select="concat('fields[', $index, '][required]')"/>
                        </xsl:call-template>
                        <xsl:call-template name="form.checkbox">
                            <xsl:with-param name="label" select="$label.display-entries"/>
                            <xsl:with-param name="key" select="concat('fields[', $index, '][display]')"/>
                        </xsl:call-template>
                    </div>
                </fieldset>
            </div>
        </li>
    </xsl:template>


    <!-- Section fields -->

	<xsl:template match="field[@type='author']" mode="section-field-name">
        <xsl:text>Author</xsl:text>
    </xsl:template>

	<xsl:template match="field[@type='author']" mode="section-field-contents">
        <xsl:param name="index"/>
        <div class="two columns">
            <xsl:call-template name="form.input">
                <xsl:with-param name="label" select="'Default number of rows'"/>
                <xsl:with-param name="key" select="concat('fields[', $index, '][default_num_rows]')"/>
                <xsl:with-param name="value" select="default_num_rows"/>
            </xsl:call-template>
        </div>
	</xsl:template>


	<!-- Input field -->

	<xsl:template match="field[@type='input']" mode="section-field-name">
        <xsl:text>Input</xsl:text>
    </xsl:template>

	<xsl:template match="field[@type='input']" mode="section-field-contents">
        <xsl:param name="index"/>
        <xsl:variable name="regex-number">
            <xsl:text disable-output-escaping="yes">/^-?(?:\d+(?:\.\d+)?|\.\d+)$/i</xsl:text>
        </xsl:variable>
        <xsl:variable name="regex-email">
            <xsl:text disable-output-escaping="yes">/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i</xsl:text>
        </xsl:variable>
        <xsl:variable name="regex-uri">
            <xsl:text disable-output-escaping="yes">/^[^\s:\/?#]+:(?:\/{2,3})?[^\s.\/?#]+(?:\.[^\s.\/?#]+)*(?:\/?[^\s?#]*\??[^\s?#]*(#[^\s#]*)?)?$/</xsl:text>
        </xsl:variable>
        <label class="column">Validation Rule
            <i>Optional</i>
            <input name="fields[{$index}][validator]" type="text"/>
        </label>
        <ul class="tags singular" data-interactive="data-interactive">
            <li data-regex="{$regex-number}">number</li>
            <li data-regex="{$regex-email}">email</li>
            <li data-regex="{$regex-uri}">URI</li>
        </ul>
	</xsl:template>


	<!-- Text Area field -->

	<xsl:template match="field[@type='textarea']" mode="section-field-name">
        <xsl:text>Text Area</xsl:text>
    </xsl:template>

	<xsl:template match="field[@type='textarea']" mode="section-field-contents">
        <xsl:param name="index"/>
        <div class="two columns">
            <xsl:call-template name="form.input">
                <xsl:with-param name="label" select="'Default number of rows'"/>
                <xsl:with-param name="key" select="concat('fields[', $index, '][default_num_rows]')"/>
                <xsl:with-param name="value" select="default_num_rows"/>
            </xsl:call-template>
            <xsl:call-template name="form.select">
                <xsl:with-param name="label" select="'Text Formatter'"/>
                <xsl:with-param name="key" select="concat('fields[', $index, '][text_formatter]')"/>
            </xsl:call-template>
        </div>
	</xsl:template>

	<!-- Date field -->

	<xsl:template match="field[@type='date']" mode="section-field-name">
        <xsl:text>Date</xsl:text>
	</xsl:template>

	<xsl:template match="field[@type='date']" mode="section-field-contents">
	</xsl:template>

</xsl:stylesheet>
