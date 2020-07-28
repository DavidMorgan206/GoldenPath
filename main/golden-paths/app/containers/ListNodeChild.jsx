import React, {Component} from "react";
import PropTypes from "prop-types";
import CustomPriceInput from "./CustomPriceInput";


export default class ListNodeChild extends Component {
    /*
    Attributes:
        flow_id
        cookie_id
    JSON:
        PageType: {ManualNode, landingNode, AffiliateNode, ListNode, Summary}
        PageAttributes: {...}

     */

    constructor(props) {
        super(props);
    }

    render() {
        let priceCell;
        if(this.props.data.allowCustomPrice) {
            priceCell =
                <td key={"price"} width="160px">
                    <div className="listNodePriceCell">
                        <CustomPriceInput price={parseFloat(this.props.price)} currencySymbol={this.props.currencySymbol}
                                      onChange={this.props.onPriceChange} readonly={!this.props.data.allowCustomPrice}/>
                    </div>
                </td>
        }
        else {
            priceCell =
                <td key={"price"} width="160px"><div className="listNodeLinkPaneCell" dangerouslySetInnerHTML={{ __html: this.props.data.linkPaneHtml}}/></td>
        }

        return (
            <div className="listNodeChild">

            <tr key={this.props.data.id}>
                <td key={"linkPane"} className="listNodeChildLinkPaneCell" width="180px">
                    <div className="listNodeChildLinkPaneHtml"
                         dangerouslySetInnerHTML={{__html: this.props.data.linkPaneHtml}}/>
                </td>
                <td key={"bodyPane"} className="listNodeChildBodyCell">
                    <label htmlFor={this.props.data.title} className="listNodeChildTitle" colSpan="5" key={"title"} >{this.props.data.title}</label>
                    <div className="listNodeChildBodyPaneHtml" dangerouslySetInnerHTML={{__html: this.props.data.bodyPaneHtml}}/>
                </td>
                {priceCell}
                <td key={"checkbox"}><input type="checkbox" id={this.props.data.title} className="listNodeChildCheckbox" defaultChecked={this.props.isChecked} onClick={this.props.onBuyChange} /></td>
            </tr>
            </div>
        )
    }



}

ListNodeChild.propTypes = {
    price: PropTypes.number,
    data: PropTypes.object,
    isChecked: PropTypes.bool,
    currencySymbol: PropTypes.string,
    onPriceChange: PropTypes.func,
    onBuyChange: PropTypes.func
}