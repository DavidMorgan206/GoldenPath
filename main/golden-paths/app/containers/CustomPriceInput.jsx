import React, {Component} from "react";
import PropTypes from "prop-types";


export default class CustomPriceInput extends Component {

    constructor(props) {
        super(props);
        this.state = {price: props.price, currencySymbol: props.currencySymbol, readonly: props.readonly};
    }

    render() {
        let readonly;
        readonly = (this.state.readonly) ? '"readonly"' : "";
        return (
                    <div className="gpathsPrice">
                        <label htmlFor="customPrice">{this.state.currencySymbol}</label>
                        <input type="number" onChange={this.props.onChange} readOnly={readonly} id="customPrice" defaultValue={this.state.price} name="customPrice" min="0" max="10000" step=".01"/>
                    </div>
        )
    }
}

CustomPriceInput.propTypes = {
    price : PropTypes.number,
    readonly: PropTypes.bool,
    currencySymbol : PropTypes.string,
    onChange: PropTypes.func
}
