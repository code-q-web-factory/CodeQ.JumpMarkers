import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {Button} from '@neos-project/react-ui-components';
import {Icon} from '@neos-project/react-ui-components';
import {connect} from 'react-redux';
import {neos} from '@neos-project/neos-ui-decorators';
import {selectors} from '@neos-project/neos-ui-redux-store';
import I18n from '@neos-project/neos-ui-i18n';
import {$get} from 'plow-js';
import style from './style.css';

@connect(state => ({
    documentNode: selectors.CR.Nodes.documentNodeSelector(state),
    focusedNode: selectors.CR.Nodes.focusedSelector(state),
    transientSectionId: $get('sectionId.value', selectors.UI.Inspector.transientValues(state))
}))
@neos(globalRegistry => ({
	i18nRegistry: globalRegistry.get('i18n')
}))

export default class AnchorView extends Component {
    static propTypes = {
        focusedNode: PropTypes.object,
        documentNode: PropTypes.object,
        transientSectionId: PropTypes.string,

		i18nRegistry: PropTypes.object.isRequired
    };

    state = {
    	// states: 'default', 'loading' 'copied'
		copyNeosLinkState: 'default',
		copyUriState: 'default',
    };

    getSectionId = () => this.props.transientSectionId || $get('properties.sectionId', this.props.focusedNode)

	copyNeosLinkToClipboard = () => {
		const documentNodeIdentifier = $get('identifier', this.props.documentNode);
		const link = 'node://' + documentNodeIdentifier + '#' + this.getSectionId();
		this.copyToClipboard(link);
		this.setState({copyNeosLinkState: 'copied', copyUriState: 'default'});
	};

	copyUriToClipboard = () => {
		this.setState({copyUriState: 'loading'});
		const redirectUri = $get('uri', this.props.documentNode).replace('neos/preview', 'neos/jump-markers-node-to-uri').replace('neos/redirect', 'neos/jump-markers-node-to-uri');
		fetch(redirectUri)
			.then(response => response.json())
			.then(response => {
				if(!response.success) {
					// ok, I'm very lazy here
					alert(response.message);
					this.setState({copyNeosLinkState: 'default', copyUriState: 'default'});
				} else {
					this.copyToClipboard(response.uri + '#' + this.getSectionId());
					this.setState({copyNeosLinkState: 'default', copyUriState: 'copied'});
				}
		});
	};

	copyToClipboard = (link) => {
		const textArea = document.createElement('textarea');
		textArea.innerText = link;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		textArea.parentNode.removeChild(textArea);
	};

	getIcon = (state) => {
		if (state === 'default') {
			return <Icon icon="copy" size="" fixedWidth padded="right" />
		} else if (state === 'loading') {
			return <Icon icon="spinner" size="" fixedWidth padded="right" spin />
		} else if (state === 'copied') {
			return <Icon icon="clipboard-check" size="" fixedWidth padded="right" />
		}
	};

    render() {
		const {i18nRegistry} = this.props;

        return this.getSectionId() && (
        	<div style={{
				display: 'flex',
				gap: '0px 8px',
			}}>
				<Button
					style="brand"
					onClick={this.copyNeosLinkToClipboard}
					className={style.copyNeosLinkButton}
					title={i18nRegistry.translate(
						'CodeQ.JumpMarkers:NodeTypes.Mixin.SectionConfiguration:properties.sectionId.copy-neos-link.tooltip',
					)}
					>
					{this.getIcon(this.state.copyNeosLinkState)}
					<I18n
						id="CodeQ.JumpMarkers:NodeTypes.Mixin.SectionConfiguration:properties.sectionId.copy-neos-link.label"
					/>
				</Button>
				<Button
					style="brand"
					onClick={this.copyUriToClipboard}
					className={style.copyUriButton}
					title={i18nRegistry.translate(
						'CodeQ.JumpMarkers:NodeTypes.Mixin.SectionConfiguration:properties.sectionId.copy-uri.tooltip'
					)}
					>
					{this.getIcon(this.state.copyUriState)}
					<I18n
						id="CodeQ.JumpMarkers:NodeTypes.Mixin.SectionConfiguration:properties.sectionId.copy-uri.label"
					/>
				</Button>
			</div>
        );
    }
}
