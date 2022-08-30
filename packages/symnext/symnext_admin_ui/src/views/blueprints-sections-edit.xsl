<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    xmlns:exsl="http://exslt.org/common"
    xmlns:func="http://exslt.org/functions"
    xmlns:form="symnext:form"
    extension-element-prefixes="form"
    exclude-result-prefixes="sn func form">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>
    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/form-field-templates.xsl"/>

	<xsl:variable name="page-title" select="'Edit'"/>
	<xsl:variable name="body-id" select="'x'"/>
    <xsl:variable name="form" select="document(/*/params/section_xml_file)/section"/>
    <xsl:variable name="validators" select="document(/*/params/validators_file)/*"/>
    <xsl:variable name="field-names" select="/*/field_names"/>
    <xsl:variable name="workspace_dirs" select="/*/workspace_dirs"/>
    <!-- Strings for the page-->
    <xsl:param name="legend.essentials">Essentials</xsl:param>
    <xsl:param name="label.name">Name</xsl:param>
	<xsl:param name="label.handle">Handle</xsl:param>
	<xsl:param name="legend.options">Options</xsl:param>
	<xsl:param name="label.hide-section">Hide this section from the back-end menu</xsl:param>
	<xsl:param name="label.allow-filtering">Allow filtering of section entries</xsl:param>
	<xsl:param name="legend.fields">Fields</xsl:param>
    <xsl:param name="label.location">Placement</xsl:param>
    <xsl:param name="label.make-required">Make this a required entry</xsl:param>
    <xsl:param name="label.display-entries">Display in entries table</xsl:param>

	<xsl:template name="context.subheading">
        <xsl:value-of select="$form/meta/name"/>
	</xsl:template>

	<xsl:template match="*" mode="context.actions">
		<li><a class="button" href="{$admin-url}/publish/{$form/meta/handle}">View Entries</a></li>
	</xsl:template>

    <xsl:template match="/data" mode="contents">
		<fieldset class="settings">
			<legend><xsl:value-of select="$legend.essentials"/></legend>
            <div class="column">
                <xsl:copy-of select="form:input(
                    $label.name, 'meta[name]',
                    $form/meta/name, 'text', 'yes', $form/meta/errors/name)"/>
            </div>
			<div class="two columns">
                <div class="column">
                    <xsl:copy-of select="form:input(
                        $label.handle, 'meta[handle]', $form/meta/handle, 'text', 'yes', $form/meta/errors/handle)"/>
                </div>
                <div class="column">
                    <xsl:copy-of select="form:input(
                        sn:__('Navigation Group'), 'meta[nav_group]', $form/meta/nav_group, 'text', 'yes', $form/meta/errors/handle)"/>
                        <ul is="selector-list" boundary="div.column" target="input[type=text]" class="tags singular">
                            <xsl:for-each select="/data/navigation/content/*">
                                <li><xsl:value-of select="./@name"/></li>
                            </xsl:for-each>
                        </ul>
                </div>
			</div>
		</fieldset>
		<fieldset class="settings">
            <legend><xsl:value-of select="$legend.options"/></legend>
            <div class="two columns">
                <div class="column">
                    <xsl:copy-of select="form:checkbox(
                        $label.hide-section, 'meta[hidden]', $form/meta/hidden)"/>
                </div>
                <div class="column">
                    <xsl:copy-of select="form:checkbox(
                        $label.allow-filtering, 'meta[filter]', $form/meta/filter)"/>
                </div>
            </div>
		</fieldset>
		<fieldset class="settings">
            <legend><xsl:value-of select="$legend.fields"/></legend>
            <p is="expand-collapse" for="fields-duplicator" class="help toggle"><a class="expand">Expand All</a><br /><a class="collapse">Collapse All</a><br /></p>
            <duplicator-frame class="frame duplicator collapsible orderable" id="fields-duplicator">
                <div>
                <!--<ol data-add="Add field" data-remove="Remove field">-->
                    <xsl:apply-templates select="$form/fields/field" mode="section-field"/>
                <!--</ol>-->
                </div>
                <fieldset class="apply">
                    <div>
                        <select>
                            <xsl:for-each select="/data/field_names/field">
                                <xsl:sort select="."/>
                                <option value="{@class}"><xsl:value-of select="."/></option>
                            </xsl:for-each>
                        </select>
                    </div>
                    <button class="constructor" type="button">Add Field</button>
                </fieldset>
            </duplicator-frame>
		</fieldset>

        <div class="actions">
            <input name="action[save]" type="submit" value="Save Changes" accesskey="s"/>
            <button name="action[delete]" class="button confirm delete" title="Delete this section" type="submit" accesskey="d" data-message="Are you sure you want to delete this section?">Delete</button>
            <input name="action[timestamp]" type="hidden" value="2022-04-16T00:19:49+01:00"/>
            <input name="action[ignore-timestamp]" type="checkbox" value="yes" class="irrelevant"/>
        </div>

		<xsl:apply-templates select="/data/field_names/field" mode="section-field"/>
    </xsl:template>

    <!-- Field templates -->
    <!--<xsl:template match="/data/fields_available/field" mode="section-field">-->
    <xsl:template match="/data/field_names/field" mode="section-field">
        <template id="{@class}">
            <xsl:call-template name="section-field">
                <xsl:with-param name="index" select="''"/>
            </xsl:call-template>
        </template>
    </xsl:template>

    <xsl:template name="section-field" match="*" mode="section-field">
        <xsl:param name="field-class" select="@class"/>
        <xsl:param name="status" select="'instance'"/>
        <xsl:param name="index" select="position() - 1"/>
        <duplicator-field class="section-field  collapsible" collapsible="collapsible" data-type="{$field-class}">
            <xsl:variable name="field-type-name" select="$field-names/*[@class=$field-class]"/>
            <xsl:variable name="name">
                <xsl:choose>
                    <xsl:when test="name!=''"><xsl:value-of select="name"/></xsl:when>
                    <xsl:otherwise>New Field</xsl:otherwise>
                </xsl:choose>
            </xsl:variable>
            <header class="frame-header {location}" data-name="{$field-type-name}">
                <h4><strong><xsl:value-of select="$name"/></strong> <span class="type"><xsl:value-of select="$field-type-name"/></span></h4><a class="destructor">Remove field</a>
            </header>
            <input name="fields[{$index}][class]" type="hidden" value="{@class}"/>
            <input name="fields[{$index}][current_handle]" type="hidden" value="{@id}"/>
            <div class="content">
                <div class="column">
                    <xsl:copy-of select="form:input(
                        $label.name,
                        concat('fields[', $index, '][name]'),
                        name, 'text', 'yes',
                        errors/name)"/>
                </div>
                <div class="two columns">
                    <div class="column">
                        <xsl:copy-of select="form:input(
                            $label.handle,
                            concat('fields[', $index, '][handle]'),
                            handle, 'text', 'text', errors/handle)"/>
                    </div>
                    <xsl:variable name="location-options">
                        <xsl:copy-of select="form:option(
                            'Main content', 'main', string(location))"/>
                        <xsl:copy-of select="form:option(
                            'Sidebar', 'sidebar', string(location))"/>
                    </xsl:variable>
                    <div class="column">
                        <xsl:copy-of select="form:select(
                            $label.location,
                            concat('fields[', $index, '][location]'),
                            $location-options)"/>
                    </div>
                </div>
                <xsl:apply-templates select="." mode="section-field-primary">
                    <xsl:with-param name="index" select="$index"/>
                </xsl:apply-templates>
                <xsl:apply-templates select="." mode="section-field-secondary">
                    <xsl:with-param name="index" select="$index"/>
                </xsl:apply-templates>
            </div>
        </duplicator-field>
    </xsl:template>

</xsl:stylesheet>
