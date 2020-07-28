import React from 'react';
import { Component } from 'react';
import PropTypes from 'prop-types';
import CustomPriceInput from "./CustomPriceInput";

export default class ManualNode extends Component {
    constructor(props) {
        super(props);
        this.state = {price :props.state.customPrice}; //customPrice could be default or a previously saved user value, we don't care
        this.customPriceEdit = this.customPriceEdit.bind(this);
    }

    customPriceEdit(e)
    {
        this.setState({price: e.target.value});
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        //if we're rendering a new node, reset price (clearing user input from previous node)
        if(prevProps.state.nodeId !== this.props.state.nodeId) {
            this.setState({price : this.props.state.customPrice});
        }
    }

    render ()
    {
        let skipButton = "";
        let price = "";
        let linkPane = "";
        let skipToSummary = "";

        if(this.props.state.skippable)
        {
            skipButton = <button id="skipButton" onClick={this.props.skipItem} className="skip-button">Skip</button>
        }

        if(this.props.state.allowCustomPrice) {
            price =
                <div id="form">
                    <form>
                        <CustomPriceInput id="customPrice" price={parseFloat(this.props.state.customPrice)} currencySymbol={this.props.state.currencySymbol} onChange={this.customPriceEdit} key={this.props.state.nodeId}/>
                    </form>
                </div>
        }
        else {
            price = <div id="defaultPrice" className="nodeDefaultPrice gpathsPrice">{this.state.price}</div>
        }

        if(this.props.state.linkPaneHtml) {
            linkPane = <div className="linkPaneHtml" id="linkPaneHtml" dangerouslySetInnerHTML={{ __html: this.props.state.linkPaneHtml}}/>
        }

        if(this.props.state.displaySkipToSummary) {
            skipToSummary = <button id="skipToSummary" className="skipToSummaryButton" onClick={this.props.skipToSummary}>Skip to Summary</button>
        }

        return (
            <div className="gpathsSingleItemNode">
                <h3 className="nodeTitle">{this.props.state.heading}</h3>

                {linkPane}

                <div id="bodyPaneHtml" className="bodyPaneHtml" dangerouslySetInnerHTML={{ __html: this.props.state.bodyPaneHtml}}/>

                {price}

                <div className="gpathsButtonsSeperator"/>
                <button id="nextButton" className="buy-button" onClick={() => this.props.buyItem(this.state.price)}>Add <i>{this.props.state.title}</i> to List</button>
                {skipButton}
                {skipToSummary}
            </div>
        )
    }
}

ManualNode.propTypes = {
    state : PropTypes.object,
    buyItem: PropTypes.func,
    skipToSummary: PropTypes.func,
    skipItem: PropTypes.func,
    defaultPrice : PropTypes.number
}
