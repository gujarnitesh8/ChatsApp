
import React, { Fragment } from 'react';
import { AppRegistry, SafeAreaView, StyleSheet } from 'react-native';
import { name as appName } from './app.json';
import { Provider } from 'react-redux';
import { store, persistor } from './src/Redux/Store';
import { PersistGate } from 'redux-persist/integration/react';
import AppNavigation from './src/AppNavigation';

export class App extends React.Component {
    constructor(props) {
        super(props)
        console.disableYellowBox = true
    }
    render() {
        return (
            <Fragment>
                <Provider store={store}>
                    <PersistGate persistor={persistor}>
                        <AppNavigation />
                    </PersistGate>
                </Provider>
            </Fragment>
        );
    };
}

AppRegistry.registerComponent(appName, () => App);
