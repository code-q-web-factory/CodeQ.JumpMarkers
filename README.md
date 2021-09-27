[![Latest Stable Version](https://poser.pugx.org/codeq/neos-link/v/stable)](https://packagist.org/packages/codeq/neos-link)
[![License](https://poser.pugx.org/codeq/neos-link/license)](LICENSE)

# CodeQ.JumpMarkers

This package makes rendering of a jump marker navigation for content nodes easy.

## Installation

CodeQ.JumpMarkers is available via packagist run `composer require codeq/jumpmarkers`.
We use semantic versioning so every breaking change will increase the major-version number.

## Usage

### 1. Configs for Neos content nodes

Add the `CodeQ.JumpMarkers:Mixin.SectionConfiguration` mixin to any content NodeType that you would want to link in a jump marker navigation, or as a permalink with a hash.

E.g. this code adds such ability to every Content NodeType:

```yaml
'Neos.Neos:Content':
  superTypes:
    'CodeQ.JumpMarkers:Mixin.SectionConfiguration': true
```

### 2. Render the anchors in the NodeType rendering

You can either render the id on the item itself with:
```
prototype(Vendor:Site) < prototype(Neos.Neos:ContentComponent) {
    id = CodeQ.JumpMarkers:Integration.Helper.NodeAnchorId
    renderer = afx`
        <section id={props.id}>
            ...
        </section
    `
```

or your add an anchor before with the `Flownative.Anchorlinks:AnchorWrapper` processor to the same NodeType renderer 
to insert an anchor tag with the given id before it.

E.g.:
```
prototype(Neos.Neos:Content) {
    @process.prependAnchor = CodeQ.JumpMarkers:AnchorWrapper
}
```

### 3. Render the jump marker navigation

Here's an example of how to render a jump marker navigation

```
prototype(CodeQ.Site:Integration.Organism.HashMenu) < prototype(Neos.Fusion:Component) {
    items = Neos.Fusion:Map {
        items = ${q(documentNode).children('main').children('[instanceof CodeQ.JumpMarkers:Mixin.SectionConfiguration][includeInHashMenu != FALSE][_hidden != TRUE]').get()}
        itemName = 'node'
        itemRenderer = CodeQ.Link:Link {
            link = CodeQ.JumpMarkers:Integration.Helper.NodeAnchorId {
                node = ${node}
                @process.wrap = ${'#' + value}
            }
            label = CodeQ.JumpMarkers:Integration.Helper.NodeTitle {
                node = ${node}
            }
        }
    }

    renderer = afx`
        <ul class="hash-menu" @if.hasMultiple={Array.length(props.items) > 1}>
            <Neos.Fusion:Loop items={props.items} itemName="item" @children="itemRenderer">
                <li @if.hasName={String.trim(item.label)}>
                    <CodeQ.Link:Tag linkSource={item}>
                        {item.label}
                    </CodeQ.Link:Tag>
                </li>
            </Neos.Fusion:Loop>
        </ul>
    `
}
```

### 4. Usage for editors

#### Jump markers
Enable the jump marker for the content NodeType you want to render in the jump marker navigation.
Then optionally define a title and anchor id manually or use the defaults.

#### Permalinks to content nodes
Choose some content node that you would like to link to, and fill in the `sectionId` property in the inspector.

After that you will see a button appear below the field to copy the link to this node to the clipboard.

When you click that button, the link to the given node will be copied to the clipboard. Paste it in the "insert link" 
field in Aloha editor, and you are done!

## Credits

This package is heavily inspired from [Flownative.Anchorlinks v1.2.0](https://github.com/flownative/neos-anchorlinks) 
with a lot of code copy-pasted, but with a different purpose. Thanks a lot to Flownative for their package!
