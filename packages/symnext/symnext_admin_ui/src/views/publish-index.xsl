<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet
	version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>

    <xsl:variable name="page-title">Sections</xsl:variable>

    <xsl:variable name="body-id">blueprints-sections</xsl:variable>

	<xsl:template name="context.subheading">
        <xsl:value-of select="/*/params/section_name"/>
	</xsl:template>

	<xsl:template match="*" mode="context.actions">
		<a class="button create" href="{$admin-url}/publish/{/data/page/section_handle}/new">Create New</a>
		<xsl:copy-of select="//context/actions"/>
		<!--<xsl:apply-templates select="//context/actions"/>-->
	</xsl:template>

    <xsl:template match="/data" mode="contents">
        <table class="selectable" role="directory" aria-labelledby="symnext-subheading">
            <thead>
                <tr>
                    <xsl:for-each select="columns/column">
                        <th scope="col"><xsl:value-of select="."/></th>
                    </xsl:for-each>
                </tr>
            </thead>
            <tbody>
                <xsl:choose>
                    <xsl:when test="count(entries/entry) > 0">
                        <xsl:apply-templates select="entries/entry"/>
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

    <xsl:template match="section/meta">
        <tr>
            <td>
                <a href="{concat($admin-url, '/blueprints/sections/edit/', handle)}" class="content"><xsl:value-of select="name"/></a>
                <label class="accessible" for="section-{handle}">Select Section <xsl:value-of select="name"/></label>
                <input name="items[{handle}]" type="checkbox" value="2021-12-16T21:30:31+00:00" id="section-{handle}"/>
            </td>
            <td></td>
            <td><xsl:value-of select="nav_group"/></td>
        </tr>
    </xsl:template>

    <xsl:template name="none-found">
        <tr class="odd">
            <td class="inactive" colspan="3">None found.</td>
        </tr>
	</xsl:template>

</xsl:stylesheet>
