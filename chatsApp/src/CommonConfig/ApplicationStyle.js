import Colors from "./Colors";
import Scale from "./Matrics";

export const ApplicationStyles = {
    shadowOpt: {
        color: "#000",
        border: 2,
        radius: 3,
        opacity: 0.2,
        x: 0,
        y: 3,
        style: { marginVertical: Scale(5) }
    },
    headerTitleStyle: {
        color: Colors.WHITE,
        fontSize: Scale(18)
    },
    headerStyle: {
        backgroundColor: Colors.APPCOLOR,
        elevation: 0,
        shadowOpacity: 0
    }
}