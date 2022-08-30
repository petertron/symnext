<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:sn="symnext:namespace"
	exclude-result-prefixes="sn">

<xsl:output method="html"
	doctype-system="about:legacy-compat"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h1><xsl:value-of select="data/params/page-title"/></h1>
</xsl:template>

</xsl:stylesheet>
