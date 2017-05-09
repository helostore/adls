function adlsHostnameFormat(uri, strict) {
    var domain = tld.getDomain(uri);
    if (!domain) {
        return null;
    }
    var subdomain = tld.getSubdomain(uri);
    var hostname = (subdomain ? subdomain + '.' : '') + domain;

    return hostname;
}

if (typeof(module) !== 'undefined') {
    module.exports = {
        adlsHostnameFormat: adlsHostnameFormat,
    };
}
