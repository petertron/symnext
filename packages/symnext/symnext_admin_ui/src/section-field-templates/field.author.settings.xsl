<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:form="symnext:form"
    xmlns:sn="symnext:namespace"
    extension-element-prefixes="form"
    exclude-result-prefixes="sn">

	<xsl:template match="*[@class='Symnext\SectionFields\FieldAuthor']" mode="section-field-primary">
        <xsl:param name="index"/>
	</xsl:template>

	<xsl:template match="*[@class='Symnext\SectionFields\FieldAuthor']" mode="section-field-secondary">
        <xsl:param name="index"/>
        <xsl:copy-of select="form:checkbox(
            'Allow selection of multiple authors',
            concat('fields[', $index, '][allow_multiple_selection]'),
            allow_multiple_selection)"/>
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
