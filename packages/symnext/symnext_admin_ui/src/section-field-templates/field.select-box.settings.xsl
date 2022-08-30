<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:form="symnext:form"
    xmlns:sn="symnext:namespace"
    extension-element-prefixes="form"
    exclude-result-prefixes="sn">

	<xsl:template match="*[@class='Symnext\SectionFields\FieldSelectBox']" mode="section-field-primary">
        <xsl:param name="index"/>
        <!--<xsl:variable name="dv-options">
            <xsl:copy-of select="form:option('None', 'none', value)"/>
            <xsl:apply-templates select="$sections/section" mode="dynamic-values"/>
        </xsl:variable>-->
        <div class="two columns">
            <div class="primary column">
                <xsl:copy-of select="form:input(
                    'Static Values',
                    concat('fields[', $index, '][static_values]'),
                    static_values)"/>
            </div>
            <div class="secondary column">
                <!--<xsl:copy-of select="form:select(
                    'Dynamic Values',
                    concat('fields[', $index, '][dynamic_values]'),
                    $dv-options)"/>-->
            </div>
        </div>
	</xsl:template>

	<xsl:template match="*[@class='Symnext\SectionFields\FieldSelectBox']" mode="section-field-secondary">
        <xsl:param name="index"/>
        <div class="two columns">
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    'Allow selection of multiple options',
                    concat('fields[', $index, '][allow_multiple]'))"/>
            </div>
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    'Sort all options alphabetically',
                    concat('fields[', $index, '][sort]'))"/>
            </div>
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    $label.make-required,
                    concat('fields[', $index, '][required]'),
                    required)"/>
            </div>
            <div class="column">
                <xsl:copy-of select="form:checkbox(
                    $label.display-entries,
                    concat('fields[', $index, '][show_column]'),
                    show_column)"/>
            </div>
        </div>
    </xsl:template>

	<xsl:template match="section" mode="dynamic-values">
        <xsl:param name="selected-value"/>
        <optgroup label="{meta/name}">
            <xsl:for-each select="fields/field">
                <xsl:copy-of select="form:option(name, handle)"/>
            </xsl:for-each>
        </optgroup>
    </xsl:template>

    <xsl:template match="text()" name="split">
        <xsl:param name="pText" select="."/>
        <xsl:if test="string-length($pText)">
            <xsl:if test="not($pText=.)">
                <br />
            </xsl:if>
            <xsl:value-of select=
                "substring-before(concat($pText, ';'), ';')"/>
            <xsl:call-template name="split">
                <xsl:with-param name="pText" select="substring-after($pText, ',')"/>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>
</xsl:stylesheet>
