<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    xmlns:exsl="http://exslt.org/common"
    xmlns:func="http://exslt.org/functions"
    xmlns:form="symnext:form"
    extension-element-prefixes="form"
    exclude-result-prefixes="sn exsl func form">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>
    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/form-field-templates.xsl"/>

	<xsl:variable name="page-title" select="'Edit'"/>
	<xsl:variable name="body-id" select="'x'"/>
    <xsl:variable name="form" select="document(/*/params/view_xml_file)/view"/>

    <xsl:param name="legend.essentials">Essentials</xsl:param>
    <xsl:param name="label.name">Name</xsl:param>
	<xsl:param name="label.handle">Handle</xsl:param>
    <xsl:param name="legend.resources">Resources</xsl:param>
    <xsl:param name="label.events">Events</xsl:param>
	<xsl:param name="label.datasources">Data Sources</xsl:param>

    <xsl:variable name="meta" select="/data/meta"/>

    <xsl:variable name="ds-result-frag">
        <xsl:for-each select="/*/datasource_files/file">
            <xsl:copy-of select="document(.)"/>
        </xsl:for-each>
    </xsl:variable>

    <xsl:variable name="datasources" select="exsl:node-set($ds-result-frag)"/>

    <xsl:template name="context.subheading">
        <xsl:value-of select="$form/meta/name"/>
	</xsl:template>

    <xsl:template match="/data" mode="contents">
		<fieldset class="settings">
			<legend><xsl:value-of select="sn:__('Essentials')"/></legend>
			<div class="two columns">
                <div class="column">
                    <xsl:copy-of select="form:input(
                        sn:__('Name'), 'meta[name]', $form/meta/name)"/>
                </div>
                <div class="column">
                    <xsl:copy-of select="form:input(
                        $label.handle, 'meta[handle]', $form/meta/handle)"/>
                </div>
			</div>
		</fieldset>
		<xsl:variable name="ds-options">
            <xsl:for-each select="$datasources/datasource">
                <xsl:sort select="name"/>
                <option value="{root_element}">
                    <xsl:if test="$form/datasources/ds[.=current()/root_element]">
                        <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                <xsl:value-of select="name"/></option>
            </xsl:for-each>
		</xsl:variable>
		<fieldset class="settings">
			<legend><xsl:value-of select="$legend.resources"/></legend>
			<div class="two columns">
                <div class="column">
                    <xsl:copy-of select="form:select(
                        $label.events, 'events', '', 'multiple')"/>
                </div>
                <label class="column"><xsl:value-of select="sn:__('Data Sources')"/>
                    <select name="resources[datasources]" multiple="multiple">
                        <xsl:for-each select="$datasources/datasource">
                            <xsl:sort select="name"/>
                            <option value="{root_element}">
                                <xsl:if test="$form/datasources/ds[.=current()/root_element]">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                <xsl:value-of select="name"/>
                            </option>
                        </xsl:for-each>
                    </select>
                </label>
			</div>
		</fieldset>
    </xsl:template>

</xsl:stylesheet>
