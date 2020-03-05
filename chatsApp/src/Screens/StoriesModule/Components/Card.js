// =======>>>>>>>> LIBRARIES <<<<<<<<=======
import React, { Fragment } from 'react';
import { View } from 'react-native';
// =======>>>>>>>> ASSETS <<<<<<<<=======
import storiesStyle from "../storiesStyle"

const Card = (props) => {
    return (
        <View style={homeStyle.cardContainer}>
            {props.children}
        </View>
    )
}
export default Card