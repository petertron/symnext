<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:form="symnext:form"
    xmlns:sn="symnext:namespace"
    extension-element-prefix="form"
    exclude-result-prefixes="sn">

	<xsl:template match="*[@class='Symnext\SectionFields\FieldTagList']" mode="section-field-primary">
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
        <xsl:variable name="sug-list">
            <xsl:copy-of select="form:option('No suggestions', 'none', pre_populate)"/>
            <xsl:copy-of select="form:option('Existing values', 'existing', pre_populate)"/>
            <!--<xsl:apply-templates select="$sections/section" mode="sug-list">
                <xsl:with-param name="selected-value" select="pre_populate_source"/>
            </xsl:apply-templates>-->
        </xsl:variable>
        <xsl:copy-of select="form:select(
            'Suggestion List',
            concat('fields[', $index, '][pre_populate_source][source][]'),
            $sug-list, 'multiple')"/>
        <div>
            <label>Validation Rule
                <i>Optional</i>
                <input name="fields[{$index}][validator]" type="text"/>
            </label>
            <ul is="selector-list" class="tags singular" boundary="div" target="input[type=text]">
                <li data-output="{$regex-number}">number</li>
                <li data-output="{$regex-email}">email</li>
                <li data-output="{$regex-uri}">URI</li>
            </ul>
        </div>
	</xsl:template>

	<xsl:template match="section" mode="sug-list">
        <xsl:param name="selected-value"/>
        <xsl:variable name="section-handle" select="meta/handle"/>
        <optgroup label="{meta/name}">
            <xsl:for-each select="fields/field">
                <xsl:copy-of select="form:option(name, concat($section-handle, '.', handle), $selected-value)"/>
            </xsl:for-each>
        </optgroup>
    </xsl:template>

	<xsl:template match="*[@class='Symnext\SectionFields\FieldTagList']" mode="section-field-secondary">
        <xsl:param name="index"/>
        <div class="two columns">
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

</xsl:stylesheet>
