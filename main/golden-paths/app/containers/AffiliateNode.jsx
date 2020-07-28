import React from 'react';
import { Component } from 'react';
import PropTypes from 'prop-types';

export default class AffiliateNode extends Component {
    constructor(props) {
        super(props);
    }

    render () {
        let skipButton = "";
        let skipToSummary = "";

        if (this.props.state.skippable) {
            skipButton = <button id="skipButton" onClick={this.props.skipItem} className="skip-button">Skip</button>
        }

        if(this.props.state.displaySkipToSummary) {
            skipToSummary = <button id="skipToSummary" className="skipToSummaryButton" onClick={this.props.skipToSummary}>Skip to Summary</button>
        }

        return (
            <div className="gpathsSingleItemNode">
                <h3 className="nodeTitle">{this.props.state.heading}</h3>

                <div className="linkPaneHtml" dangerouslySetInnerHTML={{__html: this.props.state.imagePaneHtml}}/>
                <div className="bodyPaneHtml" dangerouslySetInnerHTML={{__html: this.props.state.bodyPaneHtml}}/>
                <div className="linkPaneHtml" dangerouslySetInnerHTML={{__html: this.props.state.linkPaneHtml}}/>

                <div className="gpathsButtonsSeperator"/>

                <button id="nextButton" className="buy-button"
                        onClick={this.props.buyItem}>Add <i>{this.props.state.title}</i> to List</button>

                {skipButton}
                {skipToSummary}
            </div>
        )
    }

}

AffiliateNode.propTypes = {
    "state": PropTypes.object,
    "buyItem": PropTypes.func,
    "skipToSummary": PropTypes.func,
    "skipItem": PropTypes.func
};

