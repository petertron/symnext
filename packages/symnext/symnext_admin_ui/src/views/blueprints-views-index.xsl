<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>

    <xsl:variable name="page-title">Views</xsl:variable>

    <xsl:variable name="body-id">blueprints-views</xsl:variable>

    <xsl:template name="context.subheading">
        <xsl:value-of select="sn:__('Views')"/>
	</xsl:template>

	<xsl:template name="context.actions">
		<a class="button create" href="{$admin-url}/blueprints/views/new">Create New</a>
		<xsl:copy-of select="//context/actions"/>
		<!--<xsl:apply-templates select="//context/actions"/>-->
	</xsl:template>

	<xsl:variable name="views" select="/data/views/*"/>

	<xsl:template match="/data" mode="contents">
        <table class="selectable" role="directory" aria-labelledby="symnext-subheading">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Template</th>
                    <th scope="col">Type</th>
                </tr>
            </thead>
            <tbody>
                <xsl:choose>
                    <xsl:when test="count(views/view) > 0">
                        <xsl:apply-templates select="views/view"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:call-template name="none-found"/>
                    </xsl:otherwise>
                </xsl:choose>
            </tbody>
        </table>
        <p id="version">Symnext <xsl:value-of select="params/symnext-version"/></p>
        <div class="actions">
            <xsl:call-template name="form-apply">
                <xsl:with-param name="options">
                    <option value="delete" data-alert="{$sure-delete}">Delete</option>
                </xsl:with-param>
            </xsl:call-template>
        </div>
    </xsl:template>

    <xsl:template match="view">
        <xsl:variable name="view" select="document(.)/*"/>
        <xsl:variable name="handle" select="$view/meta/handle"/>
        <tr>
            <td>
                <a href="{concat($admin-url, '/blueprints/views/edit/', $handle)}" class="content"><xsl:value-of select="$view/meta/name"/></a>
                <label class="accessible" for="view-{$handle}">Select View <xsl:value-of select="meta/name"/></label>
                <input name="items[{$handle}]" type="checkbox" value="2021-12-16T21:30:31+00:00" id="section-{$handle}"/>
            </td>
            <td>view.<xsl:value-of select="$handle"/>.xsl</td>
        </tr>
    </xsl:template>

    <xsl:template name="none-found">
        <tr class="odd">
            <td class="inactive" colspan="3">None found.</td>
        </tr>
	</xsl:template>
</xsl:stylesheet>
