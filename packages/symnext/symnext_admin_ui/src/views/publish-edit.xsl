<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sn="symnext:namespace"
    exclude-result-prefixes="sn">

    <xsl:import href="sn://Symnext.AdminUI.VIEW_TEMPLATES/admin-layout.xsl"/>

	<xsl:variable name="page-title" select="'Edit'"/>
	<xsl:variable name="body-id" select="'x'"/>

	<xsl:template name="context.subheading">
        <xsl:text>Articul</xsl:text>
	</xsl:template>

	<xsl:template match="*" mode="context.actions">
	</xsl:template>

    <xsl:template match="/data" mode="contents">
		<fieldset class="primary column">
            <xsl:apply-templates select="fields/field[location='main']" mode="f"/>
		</fieldset>
		<fieldset class="secondary column">
            <xsl:apply-templates select="fields/field[location='sidebar']" mode="f"/>
		</fieldset>

        <div class="actions">
            <input name="action[save]" type="submit" value="Save Changes" accesskey="s"/>
            <button name="action[delete]" class="button confirm delete" title="Delete this section" type="submit" accesskey="d" data-message="Are you sure you want to delete this section?">Delete</button>
            <input name="action[timestamp]" type="hidden" value="2022-04-16T00:19:49+01:00"/>
            <input name="action[ignore-timestamp]" type="checkbox" value="yes" class="irrelevant"/>
        </div>
    </xsl:template>

    <xsl:template match="field">
    </xsl:template>


	<!-- Date field -->

	<xsl:template match="*[@type='date']" mode="section-field-name">
        <xsl:text>Date</xsl:text>
	</xsl:template>

	<xsl:template match="*[@type='date']" mode="section-field-contents">
	</xsl:template>

</xsl:stylesheet>
