<span class="adls-highlight">
    {$htmlId = "license_`$license->getId()`"}
    <input value="{$license->getLicenseKey()}" readonly="readonly" class="ty-input-text" type="text" id="{$htmlId}" size="36"/>
    <button class="ty-btn ty-btn__secondary adls-clipboard" data-clipboard-target="#{$htmlId}"
            title="{__('adls.copy_to_clipboard')}">
        <i class="fa fa-clipboard"></i>
        <span class="icon svg hidden">
            <svg xmlns="http://www.w3.org/2000/svg" width="72px" height="72px">
                <g fill="none" stroke="#8EC343" stroke-width="2">
                    <circle cx="36" cy="36" r="35"
                            style="stroke-dasharray:240px, 240px; stroke-dashoffset: 480px;"></circle>
                    <path d="M17.417,37.778l9.93,9.909l25.444-25.393"
                          style="stroke-dasharray:50px, 50px; stroke-dashoffset: 0px;"></path>
                </g>
            </svg>
        </span>
    </button>
</span>